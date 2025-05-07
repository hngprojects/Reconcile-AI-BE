<?php
// config/cors.php

return [
    'paths' => ['api/*', 'broadcasting/auth', 'sanctum/csrf-cookie', 'api/broadcasting/auth'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => [
        // Add your frontend URL here, for example:
        'http://localhost:3000',
        // If you have multiple environments:
        env('FRONTEND_URL', 'http://localhost:3000'),
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Content-Type', 'X-Requested-With', 'Authorization', 'X-CSRF-TOKEN', 'Accept'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true, // Critical for cookie/session authentication
];
