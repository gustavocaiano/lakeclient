<?php

namespace GustavoCaiano\Lakeclient\Http;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Config;

class LakeHttpClient
{
    private GuzzleClient $client;

    public function __construct(?GuzzleClient $client = null)
    {
        $baseUri = rtrim((string) Config::get('lakeclient.server.base_url', 'http://localhost'), '/'); /** @phpstan-ignore-line */
        $connectTimeout = (int) Config::get('lakeclient.server.connect_timeout', 5); /** @phpstan-ignore-line */
        $timeout = (int) Config::get('lakeclient.server.request_timeout', 10); /** @phpstan-ignore-line */
        $this->client = $client ?: new GuzzleClient([
            'base_uri' => $baseUri,
            'connect_timeout' => $connectTimeout,
            'timeout' => $timeout,
            'http_errors' => false,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    /**
     * @param  array<string,mixed>  $json
     * @return array<string,mixed>|int<min,-1>|int<1,max>|string
     *
     * @throws GuzzleException
     */
    public function post(string $uri, array $json): array|int|string
    {
        $response = $this->client->post($uri, ['json' => $json]);
        $status = $response->getStatusCode();
        /** @var array<string,mixed> $body */
        $body = json_decode((string) $response->getBody(), true) ?: [];

        return ['status' => $status, 'body' => $body];
    }
}
