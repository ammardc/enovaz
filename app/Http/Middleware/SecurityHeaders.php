<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Jangan bocorkan versi PHP/server (bahan lawan untuk mencari CVE).
        header_remove('X-Powered-By');
        $response->headers->remove('X-Powered-By');
        $response->headers->remove('Server');

        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        if (app()->isProduction()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
            $response->headers->set('Content-Security-Policy', implode('; ', [
                "default-src 'self'",
                "img-src 'self' data:",
                "style-src 'self'",
                "font-src 'self'",
                "script-src 'self'",
                "form-action 'self'",
                "frame-ancestors 'none'",
                "base-uri 'self'",
            ]));
        }

        return $response;
    }
}
