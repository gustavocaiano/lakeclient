<?php

// config for GustavoCaiano/Windclient
return [
    'server' => [
        'base_url' => env('WIND_BASE_URL', 'http://localhost'),
        'connect_timeout' => env('WIND_CONNECT_TIMEOUT', 5),
        'request_timeout' => env('WIND_REQUEST_TIMEOUT', 10),
    ],
    'license' => [
        'key' => env('WIND_LICENSE_KEY'),
        'device_name' => env('WIND_DEVICE_NAME', php_uname('n')),
    ],
    'heartbeat' => [
        // Renew a few seconds before server lease expiry. Server still dictates TTL.
        'renew_threshold_seconds' => env('WIND_HEARTBEAT_RENEW_THRESHOLD_SECONDS', 15),
        'jitter_seconds' => env('WIND_HEARTBEAT_JITTER_SECONDS', 120),
    ],
    'storage' => [
        'driver' => env('WIND_STORAGE_DRIVER', 'file'),
        // path under storage/app when using the file driver
        'path' => env('WIND_STORAGE_PATH', 'windclient/state.json'),
    ],
];
