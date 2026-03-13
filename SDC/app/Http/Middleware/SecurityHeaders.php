<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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
        $isNativePHP = env('NATIVEPHP_RUNNING') || env('NATIVE_PHP') || str_contains(strtolower(php_uname('a')), 'android');

        $csp = $this->buildCspHeader($isLocal, $isNativePHP);

        $response->headers->set('Content-Security-Policy', $csp);

        if (app()->environment('production')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        return $response;
    }

    private function buildCspHeader(bool $isLocal, bool $isNativePHP): string
    {
        // Em producao, usa cache. Em local, sempre recalcula para evitar tela branca apos rebuild
        if (!$isLocal) {
            $cacheKey = 'csp_header_' . app()->environment() . ($isNativePHP ? '_native' : '');
            return Cache::remember($cacheKey, 3600, fn() => $this->generateCspDirectives($isLocal, $isNativePHP));
        }

        return $this->generateCspDirectives($isLocal, $isNativePHP);
    }

    private function generateCspDirectives(bool $isLocal, bool $isNativePHP): string
    {
        // CDNs usados globalmente (ex: Pyodide via ai.worker.js)
        $cdnHosts = [
            'https://cdn.jsdelivr.net',
            'https://servicodados.ibge.gov.br',
        ];

        $scriptSrc = array_merge([
            "'self'",
            "'unsafe-inline'",
            "'unsafe-eval'",
            "blob:",
        ], $cdnHosts);

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

        $connectSrc = array_merge(["'self'"], $cdnHosts);

        // Em ambiente local, liberamos Vite (HTTP + WebSocket) e fontes externas usadas pelo layout
        // para evitar tela em branco por CSP bloqueando assets.
        if ($isLocal || $isNativePHP) {
            // Vite ports: internal (5173/5175) and host-mapped (15175) for Docker
            $vitePorts = [5173, 5175, 5176, 15175];
            $viteHosts = [];
            foreach ($vitePorts as $p) {
                $viteHosts[] = "http://localhost:{$p}";
                $viteHosts[] = "http://127.0.0.1:{$p}";
                $viteHosts[] = "ws://localhost:{$p}";
                $viteHosts[] = "ws://127.0.0.1:{$p}";
            }

            $scriptSrc = array_merge($scriptSrc, [
                "http://localhost:5173",
                "http://127.0.0.1:5173",
                "http://localhost:5175",
                "http://127.0.0.1:5175",
                "http://localhost:5176",
                "http://127.0.0.1:5176",
                "http://localhost:15175",
                "http://127.0.0.1:15175",
            ]);

            $connectSrc = array_merge($connectSrc, $viteHosts);

            $styleSrc[] = "https://fonts.bunny.net";
            $fontSrc[] = "https://fonts.bunny.net";

            // NativePHP/Jump: allow assets from the Jump host (APP_URL)
            $appUrl = config('app.url');
            if ($appUrl && $appUrl !== 'http://localhost') {
                $scriptSrc[] = $appUrl;
                $styleSrc[] = $appUrl;
                $connectSrc[] = $appUrl;
                $imgSrc[] = $appUrl;
                $fontSrc[] = $appUrl;

                // Allow Vite dev server on the same host (different port)
                $parsedUrl = parse_url($appUrl);
                if (!empty($parsedUrl['host'])) {
                    $scheme = $parsedUrl['scheme'] ?? 'http';
                    $host = $parsedUrl['host'];
                    foreach ([5173, 5175] as $vitePort) {
                        $scriptSrc[] = "{$scheme}://{$host}:{$vitePort}";
                        $connectSrc[] = "{$scheme}://{$host}:{$vitePort}";
                        $connectSrc[] = "ws://{$host}:{$vitePort}";
                    }
                }
            }
        }

        $workerSrc = ["'self'", 'blob:', 'https://cdn.jsdelivr.net'];
        if ($isLocal || $isNativePHP) {
            foreach ([5173, 5175, 5176, 15175] as $p) {
                $workerSrc[] = "http://localhost:{$p}";
                $workerSrc[] = "http://127.0.0.1:{$p}";
            }
        }

        return implode('; ', [
            "default-src 'self'",
            'script-src ' . implode(' ', array_unique($scriptSrc)),
            'style-src ' . implode(' ', array_unique($styleSrc)),
            'img-src ' . implode(' ', array_unique($imgSrc)),
            'font-src ' . implode(' ', array_unique($fontSrc)),
            'connect-src ' . implode(' ', array_unique($connectSrc)),
            "frame-ancestors 'self'",
            'worker-src ' . implode(' ', array_unique($workerSrc)),
        ]);
    }
}
