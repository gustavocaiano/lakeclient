## Lake Client SDK (PHP) – Implementation Plan

### Goal
Provide a PHP package (library) to embed in Laravel apps that handles license activation, daily heartbeats, and deactivation against Lake, with secure storage and optional Proof‑of‑Possession (PoP) hardening.

### MVP Scope
- Config: base URL, license key, app/device names, heartbeat interval, timeouts.
- Install identity: generate and persist an installation GUID; build a stable `device_fingerprint` (hash of GUID + OS identifiers).
- Secure storage: persist `installation_guid`, `activation_id`, `lease_token` using Laravel encryption.
- API client: JSON over HTTPS to POST `/api/licenses/activate`, `/api/licenses/heartbeat`, `/api/licenses/deactivate`.
- Lifecycle: ensure activation on boot; daily heartbeat; replace `lease_token`; block on invalid/expired.
- Errors: map 400/401/403/410; expose `isLicensed()` and `status()` helpers.

### Package Structure
- composer.json (library, PSR-4)
- src/
  - Config/LakeClientConfig.php
  - Http/LakeHttpClient.php
  - Identity/InstallationIdStore.php
  - Identity/DeviceFingerprint.php
  - Storage/SecureStore.php
  - LakeClient.php (facade entry)
  - Exceptions/*
- src/Laravel/
  - LakeClientServiceProvider.php
  - Facades/LakeClient.php
  - Console/Commands/LakeHeartbeatCommand.php
  - Middleware/EnsureLicensed.php (optional)
- config/lake-client.php (publishable)
- tests/

### Public API (proposed)
```php
LakeClient::activate(string $licenseKey = null): ActivationResult
LakeClient::heartbeat(): HeartbeatResult
LakeClient::deactivate(): void
LakeClient::isLicensed(): bool
LakeClient::status(): LicenseStatus // { active, revoked, expired, unknown }
```

### Laravel Integration
- Service provider binds config/services; registers console command.
- Publish config: `php artisan vendor:publish --tag=lake-client-config`.
- Scheduler: add `lake:heartbeat` daily or run on boot with jitter; optional route middleware `EnsureLicensed`.

### Device Fingerprint (MVP)
- `installation_guid` (UUID v4) + OS name/version + PHP version + hostname → SHA‑256, prefix `sha256:`.

### Hardening (Phase 2)
- Proof‑of‑Possession: device key pair, store private key non‑exportable if possible; send public key at activation; heartbeat signs server nonce.
- Rolling nonce/jti to prevent replay; server tracks last seen jti per activation.
- Concurrency detection: alerts/re-activation on overlaps.

### Config Keys (config/lake-client.php)
- server: base_url, connect_timeout, request_timeout
- license: key (env), device_name
- heartbeat: interval_minutes, jitter_seconds
- storage: driver file|cache|custom, path/keys

### Acceptance Criteria
- activate() stores activation_id and lease_token; idempotent when already activated
- heartbeat() refreshes lease_token; updates local store
- Proper error mapping; 401/403/410 → isLicensed() === false
- Retries with exponential backoff and jitter
- Works in Laravel: provider, publishable config, README

### CLI / Artisan
- `php artisan lake:heartbeat`
- `php artisan lake:deactivate`

### Testing
- Unit tests with mocked HTTP client (Guzzle MockHandler)
- Optional integration test against local Lake server

### Docs
- Install via Composer
- Configure .env → LAKE_CLIENT_BASE_URL, LAKE_LICENSE_KEY
- Usage example in a Laravel app service provider and scheduler

### Runtime validation strategy (important)
- Do not block web requests on network calls. Middleware should be read‑only and fast.
- Persist canonical state (encrypted): installation_guid, activation_id, lease_token, lease_expires_at, last_heartbeat_at, next_check_at.
- Middleware behavior:
  - If no activation or now ≥ lease_expires_at → deny/redirect to activation UI.
  - If within renew‑ahead lakeow (e.g., lease_expires_at − 6h) and not already queued → enqueue a heartbeat job; return normally.
  - Use a distributed lock (e.g., Cache::lock('lake:heartbeat', 60)) to avoid concurrent heartbeats.
- Scheduler/background job:
  - Run hourly or on boot: if now ≥ next_check_at → perform heartbeat.
  - On success: update lease_token, lease_expires_at, last_heartbeat_at, and set next_check_at = lease_expires_at − renew_ahead − jitter.
  - On failure with network errors: exponential backoff; keep licensed until expiry.
  - On 401/403/410: mark unlicensed and surface actionable error.
- Cache guidance:
  - Use cache only as a fast layer; TTL should be min(lease_expires_at − now − skew, maxTTL).
  - Never use a fixed 24h TTL if the server lease is shorter.
- Concurrency detection (server‑assisted):
  - Short leases + frequent heartbeats already in place.
  - Add optional PoP (signed nonces) in Phase 2 to resist fingerprint cloning.

### Future
- Add PoP (signed nonces) once server exposes public_key + nonce endpoints
- Add metrics hooks and logging adapters

### Rough Timeline
- Day 1: package scaffold, config, HTTP client, storage, identity
- Day 2: activate/heartbeat/deactivate flows + tests
- Day 3: Laravel provider, commands, docs; QA in a sample app

