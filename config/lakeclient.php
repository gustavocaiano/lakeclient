<?php

// config for GustavoCaiano/Lakeclient
return [
    'server' => [
        'base_url' => env('LAKE_BASE_URL', 'http://localhost'),
        'connect_timeout' => env('LAKE_CONNECT_TIMEOUT', 5),
        'request_timeout' => env('LAKE_REQUEST_TIMEOUT', 10),
    ],
    'license' => [
        'key' => env('LAKE_LICENSE_KEY'),
        'device_name' => env('LAKE_DEVICE_NAME', php_uname('n')),
        // Fingerprint strategy: 'guid' (stable across container restarts) or 'guid_env'
        'fingerprint_mode' => env('LAKE_FINGERPRINT_MODE', 'guid'),
    ],
    'heartbeat' => [
        // Renew a few seconds before server lease expiry. Server still dictates TTL.
        'renew_threshold_seconds' => env('LAKE_HEARTBEAT_RENEW_THRESHOLD_SECONDS', 15),
        'jitter_seconds' => env('LAKE_HEARTBEAT_JITTER_SECONDS', 120),
        // Safety margin to ensure request finishes before expiry
        'network_margin_seconds' => env('LAKE_HEARTBEAT_NETWORK_MARGIN_SECONDS', 2),
    ],
    'storage' => [
        'driver' => env('LAKE_STORAGE_DRIVER', 'file'),
        // Laravel filesystem disk to use when using the file driver (e.g., local, s3)
        'disk' => env('LAKE_STORAGE_DISK', 'local'),
        // path under the chosen disk root (for local, this is storage/app)
        'path' => env('LAKE_STORAGE_PATH', 'lakeclient/state.json'),
    ],
];
