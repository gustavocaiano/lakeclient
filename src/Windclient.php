<?php

namespace GustavoCaiano\Windclient;

use GustavoCaiano\Windclient\Contracts\StateStore;
use GustavoCaiano\Windclient\Http\WindHttpClient;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

class Windclient
{
    private StateStore $store;

    private WindHttpClient $http;

    public function __construct(StateStore $store, ?WindHttpClient $http = null)
    {
        $this->store = $store;
        $this->http = $http ?: new WindHttpClient;
    }

    public function installationGuid(): string
    {
        $state = $this->readState();
        if (! isset($state['installation_guid'])) {
            $state['installation_guid'] = (string) Str::uuid();
            $this->writeState($state);
        }

        return $state['installation_guid'];
    }

    public function deviceFingerprint(): string
    {
        $guid = $this->installationGuid();
        $os = php_uname('s').'-'.php_uname('r');
        $phpVersion = PHP_VERSION;
        $host = php_uname('n');
        $raw = $guid.'|'.$os.'|'.$phpVersion.'|'.$host;

        return 'sha256:'.hash('sha256', $raw);
    }

    public function readState(): array
    {
        return $this->store->readState();
    }

    public function writeState(array $state): void
    {
        $this->store->writeState($state);
    }

    /**
     * @return array{ok:bool,status:int|null,message?:string,body?:array}
     */
    public function activate(?string $licenseKey = null): array
    {
        $licenseKey = $licenseKey ?: (string) Config::get('windclient.license.key');
        $fingerprint = $this->deviceFingerprint();
        $deviceName = (string) Config::get('windclient.license.device_name');

        $payload = [
            'license_key' => $licenseKey,
            'device_fingerprint' => $fingerprint,
            'device_name' => $deviceName,
        ];

        $result = $this->http->post('/api/licenses/activate', $payload);
        if ($result['status'] === 200) {
            $state = $this->readState();
            $state['activation_id'] = $result['body']['activation_id'] ?? null;
            $state['lease_token'] = $result['body']['lease_token'] ?? null;
            $state['lease_expires_at'] = $result['body']['lease_expires_at'] ?? null;
            $this->writeState($state);

            return ['ok' => true, 'status' => 200, 'body' => $result['body']];
        }

        $message = match ($result['status']) {
            400 => 'Invalid input',
            409 => 'Max activations reached',
            default => 'Activation failed',
        };

        return ['ok' => false, 'status' => $result['status'], 'message' => $message, 'body' => $result['body'] ?? []];
    }

    /**
     * @return array{ok:bool,status:int|null,message?:string,body?:array}
     */
    public function heartbeat(): array
    {
        $state = $this->readState();
        if (! isset($state['activation_id'], $state['lease_token'])) {
            return ['ok' => false, 'status' => null, 'message' => 'Not activated'];
        }

        $payload = [
            'activation_id' => $state['activation_id'],
            'lease_token' => $state['lease_token'],
        ];

        $result = $this->http->post('/api/licenses/heartbeat', $payload);
        if ($result['status'] === 200) {
            $state['lease_token'] = $result['body']['lease_token'] ?? $state['lease_token'];
            $state['lease_expires_at'] = $result['body']['lease_expires_at'] ?? $state['lease_expires_at'] ?? null;
            $this->writeState($state);

            return ['ok' => true, 'status' => 200, 'body' => $result['body']];
        }

        if (in_array($result['status'], [401, 403, 410], true)) {
            $state['activation_id'] = null;
            $state['lease_token'] = null;
            $state['lease_expires_at'] = null;
            $this->writeState($state);
        }

        $message = match ($result['status']) {
            401 => 'Invalid or expired lease token',
            403 => 'Activation or license revoked',
            410 => 'License expired',
            default => 'Heartbeat failed',
        };

        return ['ok' => false, 'status' => $result['status'], 'message' => $message, 'body' => $result['body'] ?? []];
    }

    /**
     * @return array{ok:bool,status:int|null,message?:string}
     */
    public function deactivate(): array
    {
        $state = $this->readState();
        if (! isset($state['activation_id'])) {
            return ['ok' => true, 'status' => 200];
        }

        $payload = [
            'activation_id' => $state['activation_id'],
        ];

        $result = $this->http->post('/api/licenses/deactivate', $payload);
        if ($result['status'] === 200) {
            $state['activation_id'] = null;
            $state['lease_token'] = null;
            $state['lease_expires_at'] = null;
            $this->writeState($state);

            return ['ok' => true, 'status' => 200];
        }

        if (($result['status'] ?? null) === 404) {
            return ['ok' => false, 'status' => 404, 'message' => 'Activation not found'];
        }

        return ['ok' => false, 'status' => $result['status'], 'message' => 'Deactivation failed'];
    }

    public function isLicensed(): bool
    {
        $state = $this->readState();

        return isset($state['activation_id'], $state['lease_token']);
    }

    public function status(): string
    {
        return $this->isLicensed() ? 'active' : 'unknown';
    }
}
