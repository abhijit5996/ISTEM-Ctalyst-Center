<?php

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],

    'allowed_origins' => array_values(array_filter([
        'http://localhost:5173',
        'http://localhost:8080',
        'http://localhost:3000',
        'https://istem-ctalyst-center-1.onrender.com',
        env('FRONTEND_URL'),
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