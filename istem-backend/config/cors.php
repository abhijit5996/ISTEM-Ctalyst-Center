<?php

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],

    'allowed_origins' => array_values(array_filter([
        env('APP_URL'),
        env('FRONTEND_URL'),
        'http://localhost:5173',
        'http://localhost:8080',
        'http://localhost:3000',
        'http://localhost:4173',
        'http://127.0.0.1:4173',
        'https://abhijit-das.kesug.com',
    ])),

    'allowed_origins_patterns' => [
        '#^https?://.*\.onrender\.com$#',
        '#^https?://.*\.vercel\.app$#',
        '#^https?://.*\.netlify\.app$#',
    ],

    'allowed_headers' => [
        'Accept',
        'Authorization',
        'Content-Type',
        'Origin',
        'X-Requested-With',
    ],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];