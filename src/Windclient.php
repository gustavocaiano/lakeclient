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

        /** @var array<string,string> $state */
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

    /**
     * @return mixed[]
     */
    public function readState(): array
    {
        return $this->store->readState();
    }

    /**
     * @param  array<string,string>  $state
     */
    public function writeState(array $state): void
    {
        $this->store->writeState($state);
    }

    /**
     * @return array{ok:bool,status:int|null,message?:string,body?:int|null|string[]}
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function activate(?string $licenseKey = null): array
    {
        $licenseKey = $licenseKey ?: (string) Config::get('windclient.license.key'); /** @phpstan-ignore-line */
        $fingerprint = $this->deviceFingerprint();
        $deviceName = (string) Config::get('windclient.license.device_name'); /** @phpstan-ignore-line */
        $payload = [
            'license_key' => $licenseKey,
            'device_fingerprint' => $fingerprint,
            'device_name' => $deviceName,
        ];
        /** @var array<string,int|null> $result */
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
     * @return array{ok:bool,status:int|null,message?:string,body?:string[]|int}
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
        /** @var array<string,int> $result */
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
        /** @var array<string,int|null> $result */
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

        if (! isset($state['activation_id'], $state['lease_token'])) {
            return false;
        }

        // Enforce lease TTL locally if provided by the server
        $expiresAt = $this->getLeaseExpiry();
        if ($expiresAt !== null) {
            try {
                $now = new \DateTimeImmutable('now');
                if ($now >= $expiresAt) {
                    return false;
                }
            } catch (\Throwable) {
                // If we cannot parse the date, fall back to token presence
            }
        }

        return true;
    }

    public function status(): string
    {
        return $this->isLicensed() ? 'active' : 'unknown';
    }

    /**
     * Returns the lease expiry timestamp provided by the server, if available.
     */
    public function getLeaseExpiry(): ?\DateTimeImmutable
    {
        $state = $this->readState();
        $expiresAtRaw = $state['lease_expires_at'] ?? null;
        if ($expiresAtRaw === null) {
            return null;
        }
        try {
            if (is_numeric($expiresAtRaw)) {
                return (new \DateTimeImmutable('@'.((string) (int) $expiresAtRaw)))->setTimezone(new \DateTimeZone(date_default_timezone_get()));
            }
            $parsed = strtotime((string) $expiresAtRaw);
            if ($parsed !== false) {
                return (new \DateTimeImmutable('@'.$parsed))->setTimezone(new \DateTimeZone(date_default_timezone_get()));
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }

    /**
     * Number of seconds until the lease expires (server-driven). Null if unknown.
     */
    public function secondsUntilLeaseExpiry(): ?int
    {
        $expiry = $this->getLeaseExpiry();
        if ($expiry === null) {
            return null;
        }
        $now = new \DateTimeImmutable('now');
        return $expiry->getTimestamp() - $now->getTimestamp();
    }

    /**
     * Whether the client should renew now, based on a small expiry threshold.
     */
    public function shouldRenewLease(?int $thresholdSeconds = null): bool
    {
        if ($thresholdSeconds === null) {
            /** @var int $thresholdSeconds */
            $thresholdSeconds = (int) \Illuminate\Support\Facades\Config::get('windclient.heartbeat.renew_threshold_seconds', 15);
        }
        $secondsLeft = $this->secondsUntilLeaseExpiry();
        if ($secondsLeft === null) {
            // No expiry known, renew to fetch server TTL
            return true;
        }
        return $secondsLeft <= $thresholdSeconds;
    }
}
