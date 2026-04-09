<?php

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:5173',      // Vite dev server
        'http://localhost:8080',      // Alternative dev
        'http://localhost:3000',      // Node dev
        'https://istem-catalyst-center.onrender.com', // Production frontend URL
        'https://istem-catalyst-center-1.onrender.com', // Alternative production URL
        env('FRONTEND_URL', 'http://localhost:5173'), // Env override
    ],

    'allowed_origins_patterns' => [
        '#https?://.*\.onrender\.com#',     // Render deployments (fallback)
        '#https?://.*\.vercel\.app#',       // Vercel deployments
        '#https?://.*\.netlify\.app#',      // Netlify deployments
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];