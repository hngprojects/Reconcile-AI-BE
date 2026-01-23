<?php
// config/cors.php

return [
    'paths' => ['api/*', 'broadcasting/auth', 'sanctum/csrf-cookie', 'api/broadcasting/auth'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => array_filter([
        'http://localhost:3000',
        'http://127.0.0.1:3000',
        env('FRONTEND_URL', 'http://localhost:3000'),
        env('STAGING_FRONTEND_URL'),
        env('PRODUCTION_FRONTEND_URL'),
    ]),

    'allowed_origins_patterns' => [
        '#^https?://.*\.reconxi\.com$#',  // Match any subdomain of reconxi.com
    ],

    'allowed_headers' => ['Content-Type', 'X-Requested-With', 'Authorization', 'X-CSRF-TOKEN', 'Accept', 'Origin', 'X-Auth-Token'],

    'exposed_headers' => [],

    'max_age' => 86400, // Cache preflight for 24 hours

    'supports_credentials' => true, // Critical for cookie/session authentication
];
