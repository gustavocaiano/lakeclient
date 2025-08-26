<?php

namespace GustavoCaiano\Windclient\Http;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Config;

class WindHttpClient
{
    private GuzzleClient $client;

    public function __construct(?GuzzleClient $client = null)
    {
        $baseUri = rtrim((string) Config::get('windclient.server.base_url', 'http://localhost'), '/');
        $connectTimeout = (int) Config::get('windclient.server.connect_timeout', 5);
        $timeout = (int) Config::get('windclient.server.request_timeout', 10);

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
     * @return array{status:int,body:array}
     *
     * @throws GuzzleException
     */
    public function post(string $uri, array $json): array
    {
        $response = $this->client->post($uri, ['json' => $json]);
        $status = $response->getStatusCode();
        $body = json_decode((string) $response->getBody(), true) ?: [];

        return ['status' => $status, 'body' => $body];
    }
}
