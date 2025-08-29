<?php

namespace Tests\Feature;

use GustavoCaiano\Lakeclient\Filament\Pages\LicensePage;
use GustavoCaiano\Lakeclient\Http\LakeHttpClient;
use GustavoCaiano\Lakeclient\Lakeclient;
use Livewire\Livewire;
use Tests\TestCase;

class LicensePageTest extends TestCase
{
    /**
     * @param  array<int,string|array<string,mixed>|int>  $responses
     */
    private function mockHttp(array $responses): void
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

        $this->app?->instance(LakeHttpClient::class, $fake);
        $this->app?->forgetInstance(Lakeclient::class);
    }

    public function test_page_activation_success(): void
    {
        $this->mockHttp([
            [
                'status' => 200,
                'body' => [
                    'activation_id' => 'a',
                    'lease_token' => 't',
                    'lease_expires_at' => '2025-01-01T00:00:00Z',
                ],
            ],
        ]);

        Livewire::test(LicensePage::class)
            ->set('license_key', 'abc-123')
            ->call('submit');

        expect(app(Lakeclient::class)->isLicensed())->toBeTrue();
    }

    public function test_page_activation_conflict(): void
    {
        $this->mockHttp([
            [
                'status' => 409,
                'body' => [],
            ],
        ]);

        Livewire::test(LicensePage::class)
            ->set('license_key', 'bad-key')
            ->call('submit');

        expect(app(Lakeclient::class)->isLicensed())->toBeFalse();
    }
}
