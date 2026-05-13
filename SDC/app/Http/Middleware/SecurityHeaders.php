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
        $viteDevActive = $isLocal || $isNativePHP || file_exists(public_path('hot'));

        $csp = $this->buildCspHeader($isLocal, $isNativePHP, $viteDevActive);

        $response->headers->set('Content-Security-Policy', $csp);

        if (app()->environment('production')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        return $response;
    }

    private function buildCspHeader(bool $isLocal, bool $isNativePHP, bool $viteDevActive): string
    {
        // Em producao sem Vite dev, usa cache. Em local ou com hot file ativo, sempre recalcula
        // para evitar tela branca apos rebuild ou divergencia quando o dev server liga/desliga
        if (!$viteDevActive) {
            $cacheKey = 'csp_header_' . app()->environment() . ($isNativePHP ? '_native' : '');
            return Cache::remember($cacheKey, 3600, fn() => $this->generateCspDirectives($isLocal, $isNativePHP, $viteDevActive));
        }

        return $this->generateCspDirectives($isLocal, $isNativePHP, $viteDevActive);
    }

    private function generateCspDirectives(bool $isLocal, bool $isNativePHP, bool $viteDevActive): string
    {
        $scriptSrc = [
            "'self'",
            "'unsafe-inline'",
            "'unsafe-eval'",
            "blob:",
            "https://cdn.jsdelivr.net",
        ];

        $styleSrc = [
            "'self'",
            "'unsafe-inline'",
            "https://fonts.bunny.net",
        ];

        // Allow app URL
        $appUrl = config('app.url');
        if ($appUrl) {
            $scriptSrc[] = $appUrl;
            $styleSrc[] = $appUrl;
            $connectSrc[] = $appUrl;
            $imgSrc[] = $appUrl;
        }

        $imgSrc = [
            "'self'",
            "data:",
            "https:",
        ];

        $fontSrc = [
            "'self'",
            "data:",
            "https://fonts.bunny.net",
        ];

        $connectSrc = [
            "'self'",
            "https://cdn.jsdelivr.net",
        ];

        // Quando Vite dev esta ativo (ambiente local, NativePHP ou hot file presente),
        // liberamos Vite (HTTP + WebSocket) e fontes externas usadas pelo layout
        // para evitar tela em branco por CSP bloqueando assets.
        if ($viteDevActive) {
            // Vite ports: internal (5173/5175), host-mapped (15175) for Docker, and HMR WS (18081)
            $vitePorts = [5173, 5175, 5176, 8081, 15175, 18081];
            $viteHosts = [];
            foreach ($vitePorts as $p) {
                $viteHosts[] = "http://localhost:{$p}";
                $viteHosts[] = "http://127.0.0.1:{$p}";
                $viteHosts[] = "ws://localhost:{$p}";
                $viteHosts[] = "ws://127.0.0.1:{$p}";
            }

            $scriptSrc = array_merge($scriptSrc, [
                "http://localhost:*",
                "http://127.0.0.1:*",
            ]);

            $styleSrc = array_merge($styleSrc, [
                "http://localhost:*",
                "http://127.0.0.1:*",
            ]);

            $connectSrc = array_merge($connectSrc, $viteHosts, [
                'https://servicodados.ibge.gov.br',
                'https://viacep.com.br',
                'https://nominatim.openstreetmap.org',
                'http://host.docker.internal:8000',
                'http://localhost:18001',
                'http://127.0.0.1:18001',
            ]);

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

        $workerSrc = "'self' blob: data: https://cdn.jsdelivr.net";
        if ($viteDevActive) {
            $workerSrc .= " http://localhost:8081 http://127.0.0.1:8081 http://localhost:15175 http://127.0.0.1:15175 http://localhost:5175 http://127.0.0.1:5175";
        }

        return implode('; ', [
            "default-src 'self'",
            'script-src ' . implode(' ', array_unique($scriptSrc)),
            'style-src ' . implode(' ', array_unique($styleSrc)),
            'img-src ' . implode(' ', array_unique($imgSrc)),
            'font-src ' . implode(' ', array_unique($fontSrc)),
            'connect-src ' . implode(' ', array_unique($connectSrc)),
            "frame-ancestors 'self'",
            "worker-src {$workerSrc}",
        ]);
    }
}
