<?php

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_values(array_filter([
        'http://localhost:5173',
        'http://localhost:8080',
        'http://localhost:3000',
        env('FRONTEND_URL'),
        env('APP_URL'),
    ])),

    'allowed_origins_patterns' => [
        '#^https?://.*\.onrender\.com$#',
        '#^https?://.*\.vercel\.app$#',
        '#^https?://.*\.netlify\.app$#',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];