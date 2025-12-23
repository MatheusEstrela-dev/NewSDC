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

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');

        $isLocal = app()->environment(['local', 'development']);

        $scriptSrc = [
            "'self'",
            "'unsafe-inline'",
            "'unsafe-eval'",
        ];

        $styleSrc = [
            "'self'",
            "'unsafe-inline'",
        ];

        $imgSrc = [
            "'self'",
            "data:",
            "https:",
        ];

        $fontSrc = [
            "'self'",
            "data:",
        ];

        $connectSrc = [
            "'self'",
        ];

        // Em ambiente local, liberamos Vite (HTTP + WebSocket) e fontes externas usadas pelo layout
        // para evitar tela em branco por CSP bloqueando assets.
        if ($isLocal) {
            $viteHosts = [
                "http://localhost:5173",
                "http://127.0.0.1:5173",
                "ws://localhost:5173",
                "ws://127.0.0.1:5173",
                "http://localhost:5175",
                "http://127.0.0.1:5175",
                "ws://localhost:5175",
                "ws://127.0.0.1:5175",
            ];

            $scriptSrc = array_merge($scriptSrc, [
                "http://localhost:5173",
                "http://127.0.0.1:5173",
                "http://localhost:5175",
                "http://127.0.0.1:5175",
            ]);

            $connectSrc = array_merge($connectSrc, $viteHosts);

            $styleSrc[] = "https://fonts.bunny.net";
            $fontSrc[] = "https://fonts.bunny.net";
        }

        $csp = implode('; ', [
            "default-src 'self'",
            'script-src ' . implode(' ', array_unique($scriptSrc)),
            'style-src ' . implode(' ', array_unique($styleSrc)),
            'img-src ' . implode(' ', array_unique($imgSrc)),
            'font-src ' . implode(' ', array_unique($fontSrc)),
            'connect-src ' . implode(' ', array_unique($connectSrc)),
            "frame-ancestors 'self'",
        ]);

        $response->headers->set('Content-Security-Policy', $csp);

        if (app()->environment('production')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        return $response;
    }
}
