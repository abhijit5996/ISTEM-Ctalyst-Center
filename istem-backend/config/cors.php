<?php

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:5173',      // Vite dev server
        'http://localhost:8080',      // Alternative dev
        'http://localhost:3000',      // Node dev
        env('FRONTEND_URL', 'http://localhost:5173'), // Production frontend
    ],

    'allowed_origins_patterns' => [
        '#https?://.*\.onrender\.com#',     // Render deployments
        '#https?://.*\.vercel\.app#',       // Vercel deployments
        '#https?://.*\.netlify\.app#',      // Netlify deployments
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];