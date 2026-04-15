<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HandleCors
{
    /**
     * The paths that should be excluded from CORS checks.
     *
     * @var array
     */
    protected $except = [
        //
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get allowed origins from config
        $allowedOrigins = config('cors.allowed_origins', []);
        $allowedPatterns = config('cors.allowed_origins_patterns', []);
        $origin = $request->header('Origin');
        $allowedMethods = config('cors.allowed_methods', ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS']);
        $allowedHeaders = config('cors.allowed_headers', ['Accept', 'Authorization', 'Content-Type', 'Origin', 'X-Requested-With']);

        $isAllowed = false;

        // Check exact origins
        if (in_array($origin, $allowedOrigins)) {
            $isAllowed = true;
        }

        // Check pattern-based origins
        if (! $isAllowed && $origin) {
            foreach ($allowedPatterns as $pattern) {
                if (preg_match($pattern, $origin)) {
                    $isAllowed = true;
                    break;
                }
            }
        }

        // If origin is allowed, add CORS headers
        if ($isAllowed) {
            // Handle preflight OPTIONS request
            if ($request->isMethod('OPTIONS')) {
                return response('', 200)
                    ->header('Access-Control-Allow-Origin', $origin)
                    ->header('Access-Control-Allow-Methods', implode(', ', $allowedMethods))
                    ->header('Access-Control-Allow-Headers', implode(', ', $allowedHeaders))
                    ->header('Access-Control-Max-Age', config('cors.max_age', 0))
                    ->header('Access-Control-Allow-Credentials', config('cors.supports_credentials', false) ? 'true' : 'false')
                    ->header('Vary', 'Origin');
            }

            // Add CORS headers to response
            $response = $next($request);
            $response->header('Access-Control-Allow-Origin', $origin);
            $response->header('Access-Control-Allow-Methods', implode(', ', $allowedMethods));
            $response->header('Access-Control-Allow-Headers', implode(', ', $allowedHeaders));
            $response->header('Access-Control-Max-Age', config('cors.max_age', 0));
            $response->header('Vary', 'Origin');

            if (config('cors.supports_credentials', false)) {
                $response->header('Access-Control-Allow-Credentials', 'true');
            }

            return $response;
        }

        return $next($request);
    }
}
