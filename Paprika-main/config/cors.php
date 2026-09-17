<?php

/*
|--------------------------------------------------------------------------
| Cross-Origin Resource Sharing (CORS) Configuration
|--------------------------------------------------------------------------
|
| Here you may configure your settings for cross-origin resource sharing
| or "CORS". This determines what cross-origin operations may execute
| in web browsers. You are free to adjust these settings as needed.
|
| To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
|
*/

return [

    'paths' => [
        'api/*',
        'storage/*',
        'paprika/*',
        'sanctum/csrf-cookie',
    ],

    'allowed_methods' => ['*'],

    /*
    | Allowed origins - reads from CORS_ALLOWED_ORIGINS env var.
    | For local dev, also include all localhost ports.
    | Format: https://example.com,https://app.example.com
    */
    'allowed_origins' => array_filter(array_map('trim', explode(',', env('CORS_ALLOWED_ORIGINS', '*')))),

    /*
    | Note: allowed_origins_patterns uses regex without delimiters.
    | The fruitcake/cors library adds delimiters automatically.
    | For local dev with random ports, add ports explicitly in allowed_origins.
    */
    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 86400,

    'supports_credentials' => false,
];
