<?php

namespace GustavoCaiano\Lakeclient;

use Carbon\CarbonImmutable;
use GustavoCaiano\Lakeclient\Contracts\StateStore;
use GustavoCaiano\Lakeclient\Http\LakeHttpClient;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

class Lakeclient
{
    private StateStore $store;

    private LakeHttpClient $http;

    public function __construct(StateStore $store, ?LakeHttpClient $http = null)
    {
        $this->store = $store;
        $this->http = $http ?: new LakeHttpClient;
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
        /** @var string $mode */
        $mode = (string) Config::get('lakeclient.license.fingerprint_mode', 'guid'); /** @phpstan-ignore-line */
        if ($mode === 'guid') {
            $secret = (string) Config::get('app.key', '');
            $digest = hash_hmac('sha256', $guid, $secret);
            return 'sha256:'.$digest;
        }

        // 'guid_env' mixes environment details. This may change across container rebuilds.
        $os = php_uname('s').'-'.php_uname('r');
        $phpVersion = PHP_VERSION;
        $host = php_uname('n');
        $raw = $guid.'|'.$os.'|'.$phpVersion.'|'.$host;

        $secret = (string) Config::get('app.key', '');
        $digest = hash_hmac('sha256', $raw, $secret);
        return 'sha256:'.$digest;
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
        $state = $this->readState();
        $licenseKey = $licenseKey
            ?: (string) (Config::get('lakeclient.license.key') ?? '')
            ?: (string) ($state['license_key'] ?? ''); /** @phpstan-ignore-line */
        if ($licenseKey === '') {
            return ['ok' => false, 'status' => null, 'message' => 'License key not configured'];
        }
        $fingerprint = $this->deviceFingerprint();
        $deviceName = (string) Config::get('lakeclient.license.device_name'); /** @phpstan-ignore-line */
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
            $state['license_key'] = $licenseKey;
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
            // Try to (re)activate if we have a license key available
            $activateResult = $this->activate(null);
            if ($activateResult['ok'] ?? false) {
                return ['ok' => true, 'status' => 200, 'body' => $activateResult['body'] ?? []];
            }

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
            if ($result['status'] === 401) {
                // Token invalid/expired: attempt transparent re-activation (server is authoritative)
                $reactivate = $this->activate(null);
                if ($reactivate['ok'] ?? false) {
                    return ['ok' => true, 'status' => 200, 'body' => $reactivate['body'] ?? []];
                }
            }

            // Clear volatile activation data but keep stored license_key for future re-activation
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
                $nowTs = CarbonImmutable::now((string) (Config::get('app.timezone') ?: date_default_timezone_get()))->getTimestamp();
                if ($nowTs >= $expiresAt->getTimestamp()) {
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
    public function getLeaseExpiry(): ?CarbonImmutable
    {
        $state = $this->readState();
        $expiresAtRaw = $state['lease_expires_at'] ?? null;
        if ($expiresAtRaw === null) {
            return null;
        }
        try {
            $tz = (string) (Config::get('app.timezone') ?: date_default_timezone_get());
            if (is_numeric($expiresAtRaw)) {
                return CarbonImmutable::createFromTimestamp((int) $expiresAtRaw, $tz);
            }
            return CarbonImmutable::parse((string) $expiresAtRaw, $tz);
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
        $nowTs = CarbonImmutable::now((string) (Config::get('app.timezone') ?: date_default_timezone_get()))->getTimestamp();
        return $expiry->getTimestamp() - $nowTs;
    }

    /**
     * Whether the client should renew now, based on a small expiry threshold.
     */
    public function shouldRenewLease(?int $thresholdSeconds = null): bool
    {
        if ($thresholdSeconds === null) {
            /** @var int $thresholdSeconds */
            $thresholdSeconds = (int) \Illuminate\Support\Facades\Config::get('lakeclient.heartbeat.renew_threshold_seconds', 15);
        }
        $secondsLeft = $this->secondsUntilLeaseExpiry();
        if ($secondsLeft === null) {
            // No expiry known, renew to fetch server TTL
            return true;
        }
        return $secondsLeft <= $thresholdSeconds;
    }
}
