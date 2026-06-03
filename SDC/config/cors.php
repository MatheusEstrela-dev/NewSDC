<?php

$defaultAllowedOrigins = [
    'https://sdcdefesa.azurewebsites.net',
    'https://newsdc2027.azurewebsites.net',
    'https://localhost:19444',
    'http://localhost:3000',
    'http://localhost:5173',
    'http://localhost:18001',
    'http://127.0.0.1:18001',
];

$allowedOrigins = env('CORS_ALLOWED_ORIGINS')
    ? array_map('trim', explode(',', env('CORS_ALLOWED_ORIGINS')))
    : $defaultAllowedOrigins;

// CORS with credentials must never use a wildcard origin.
$allowedOrigins = array_values(array_filter($allowedOrigins, fn ($origin) => $origin !== '*'));

return [

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

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => $allowedOrigins,

    'allowed_origins_patterns' => [],

    'allowed_headers' => [
        'Content-Type',
        'Authorization',
        'X-Requested-With',
        'Accept',
        'Origin',
        'X-CSRF-TOKEN',
        'X-XSRF-TOKEN',
        'X-PowerBI-Token',
    ],

    'exposed_headers' => [
        'X-RateLimit-Limit',
        'X-RateLimit-Remaining',
        'X-RateLimit-Reset',
    ],

    'max_age' => 86400,

    'supports_credentials' => true,

];
