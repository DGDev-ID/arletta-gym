<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for cross-origin resource sharing. The landing page SPA runs
    | on a separate origin and needs CORS to call these API endpoints.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        env('LANDING_URL', 'http://localhost:5173'),  // Vite dev server
        'http://localhost:5174',                       // Vite alternate port
        env('APP_URL', 'http://localhost:8000'),
        'https://gym.arlettaluxury.com',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
