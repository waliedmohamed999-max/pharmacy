<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $headers = $response->headers;
        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('X-Frame-Options', 'DENY');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('X-Permitted-Cross-Domain-Policies', 'none');
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');

        if ($request->isSecure()) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        if (!$headers->has('Content-Security-Policy') && !$request->expectsJson()) {
            $headers->set(
                'Content-Security-Policy',
                "default-src 'self'; base-uri 'self'; frame-ancestors 'none'; object-src 'none'; ".
                "img-src 'self' data: https:; font-src 'self' data: https://fonts.gstatic.com; ".
                "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; ".
                "script-src 'self' 'unsafe-inline'; connect-src 'self' https:; form-action 'self'"
            );
        }

        return $response;
    }
}
