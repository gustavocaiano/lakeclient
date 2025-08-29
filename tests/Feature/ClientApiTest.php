<?php

namespace Tests\Feature;

use GustavoCaiano\Lakeclient\Http\LakeHttpClient;
use GustavoCaiano\Lakeclient\Lakeclient;
use Tests\TestCase;

class ClientApiTest extends TestCase
{
    /**
     * @param  array<int,string|array<string,mixed>|int>  $responses
     */
    private function setHttpResponseSequence(Lakeclient $client, array $responses): void
    {
        $fake = new class($responses) extends LakeHttpClient
        {
            /**
             * @var array<int,string|array<string,mixed>|int>
             */
            private array $responses;

            /**
             * @param  array<int, string|array<string,mixed>|int>  $responses
             */
            public function __construct(array $responses)
            {
                $this->responses = $responses;
            }

            public function post(string $uri, array $json): array|int|string
            {
                return array_shift($this->responses) ?: ['status' => 500, 'body' => []];
            }
        };

        // Swap binding in container so any resolved Lakeclient uses the fake HTTP client
        $this->app?->instance(LakeHttpClient::class, $fake);
        // Ensure a fresh Lakeclient singleton is constructed with the fake bound
        $this->app?->forgetInstance(Lakeclient::class);
    }

    public function test_activate_success(): void
    {
        $this->setHttpResponseSequence(app(Lakeclient::class), [[
            'status' => 200,
            'body' => [
                'activation_id' => 'a',
                'lease_token' => 't',
                'lease_expires_at' => '2025-01-01T00:00:00Z',
            ],
        ]]);

        $client = app(Lakeclient::class);
        $result = $client->activate('key');
        expect($result['ok'])->toBeTrue();
        expect($client->isLicensed())->toBeTrue();
    }

    public function test_activate_conflict(): void
    {
        $this->setHttpResponseSequence(app(Lakeclient::class), [[
            'status' => 409,
            'body' => [],
        ]]);
        $client = app(Lakeclient::class);
        $result = $client->activate('key');
        expect($result['ok'])->toBeFalse();
        expect($result['status'])->toBe(409);
    }

    public function test_heartbeat_unlicensed(): void
    {
        $client = app(Lakeclient::class);
        $client->writeState([]);
        $result = $client->heartbeat();
        expect($result['ok'])->toBeFalse();
        expect($result['message'] ?? '')->toBe('Not activated');
    }

    public function test_heartbeat_revoked_clears_state(): void
    {
        $client = app(Lakeclient::class);
        $client->writeState(['activation_id' => 'a', 'lease_token' => 't']);
        $this->setHttpResponseSequence(app(Lakeclient::class), [[
            'status' => 403,
            'body' => [],
        ]]);
        // Re-resolve client to pick up mocked HTTP
        $client = app(Lakeclient::class);
        $result = $client->heartbeat();
        expect($result['ok'])->toBeFalse();
        expect($client->isLicensed())->toBeFalse();
    }

    public function test_deactivate_success(): void
    {
        $client = app(Lakeclient::class);
        $client->writeState(['activation_id' => 'a', 'lease_token' => 't']);
        $this->setHttpResponseSequence(app(Lakeclient::class), [[
            'status' => 200,
            'body' => [],
        ]]);
        $client = app(Lakeclient::class);
        $result = $client->deactivate();
        expect($result['ok'])->toBeTrue();
        expect($client->isLicensed())->toBeFalse();
    }
}
