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
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');

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

    /** Memoizacao em processo do CSP por worker Octane (chave: env[_native]). */
    private static array $cspCache = [];

    private function buildCspHeader(bool $isLocal, bool $isNativePHP, bool $viteDevActive): string
    {
        // Vite dev (local/native/hot file): sempre recalcula — o estado muda em
        // runtime quando o dev server liga/desliga (evita tela branca por CSP).
        if ($viteDevActive) {
            return $this->generateCspDirectives($isLocal, $isNativePHP, $viteDevActive);
        }

        // Producao: memoiza EM PROCESSO (por worker), nao no Redis. O CSP so
        // varia por environment/native — constantes dentro do worker. Sob Octane
        // o worker persiste, entao calcula 1x por worker e reusa, eliminando um
        // GET no Redis em TODA request (era hot path de 100% do trafego).
        $cacheKey = app()->environment() . ($isNativePHP ? '_native' : '');

        return self::$cspCache[$cacheKey] ??= $this->generateCspDirectives($isLocal, $isNativePHP, $viteDevActive);
    }

    private function generateCspDirectives(bool $isLocal, bool $isNativePHP, bool $viteDevActive): string
    {
        $scriptSrc = [
            "'self'",
            "'unsafe-inline'",
            "blob:",
            "https://cdn.jsdelivr.net",
        ];

        if ($viteDevActive) {
            $scriptSrc[] = "'unsafe-eval'";
        }

        $styleSrc = [
            "'self'",
            "'unsafe-inline'",
            "https://fonts.bunny.net",
        ];

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
            // Best-effort para auditoria de IP do client (composable useClientIp).
            // Bloqueio = sem dado, nao quebra fluxo.
            "https://api.ipify.org",
            "https://ipapi.co",
            // APIs externas chamadas pelo frontend tambem em PRODUCAO:
            // - IBGE: fallback de municipios para UFs fora da base local (useLocationData)
            // - ViaCEP: busca de endereco por CEP (useCep)
            // - Nominatim: geocoding do endereco (useCep)
            // Sem estas no connect-src, o navegador bloqueia o fetch e o select
            // de municipio/CEP fica vazio para qualquer UF que dependa do fallback.
            "https://servicodados.ibge.gov.br",
            "https://viacep.com.br",
            "https://nominatim.openstreetmap.org",
        ];

        // WebSocket do Reverb (tempo real). FORA do bloco de Vite dev abaixo: o
        // broadcasting roda em producao tambem, e a porta do Reverb nao e porta
        // de Vite -- ela nao estava em lista nenhuma, entao o navegador
        // bloqueava a conexao por connect-src e o console enchia de
        // "Content-Security-Policy: ... bloquearam o carregamento de um recurso
        // (connect-src) em wss://localhost:8080/app/...".
        //
        // O efeito nao era so ruido: sem WebSocket, as telas do medalhao param
        // de atualizar sozinhas e o operador ve dado velho sem nenhum aviso.
        //
        // Os dois esquemas entram porque o cliente (pusher-js) tem
        // `enabledTransports: ['ws', 'wss']` e pode tentar o seguro mesmo em
        // ambiente http; liberar wss num host que so fala ws nao cria risco --
        // a conexao simplesmente falha no TLS, com erro proprio e diagnosticavel,
        // em vez de morrer no CSP.
        $connectSrc = array_merge($connectSrc, $this->origensDoWebsocket());

        // Allow app URL
        $appUrl = config('app.url');
        if ($appUrl) {
            $scriptSrc[] = $appUrl;
            $styleSrc[] = $appUrl;
            $connectSrc[] = $appUrl;
            $imgSrc[] = $appUrl;
        }

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

            $imgSrc = array_merge($imgSrc, [
                "http://localhost:*",
                "http://127.0.0.1:*",
            ]);

            $connectSrc = array_merge($connectSrc, $viteHosts, [
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

        return implode('; ', array_filter([
            "default-src 'self'",
            "base-uri 'self'",
            'script-src ' . implode(' ', array_unique($scriptSrc)),
            'style-src ' . implode(' ', array_unique($styleSrc)),
            'img-src ' . implode(' ', array_unique($imgSrc)),
            'font-src ' . implode(' ', array_unique($fontSrc)),
            'connect-src ' . implode(' ', array_unique($connectSrc)),
            "object-src 'none'",
            "form-action 'self'",
            "frame-ancestors 'self'",
            "worker-src {$workerSrc}",
            app()->environment('production') ? 'upgrade-insecure-requests' : '',
        ]));
    }

    /**
     * Origens que o cliente usa para abrir o WebSocket do broadcasting.
     *
     * Derivadas da config, nao fixas: a porta do Reverb e configuravel por
     * REVERB_PORT, e uma lista com "8080" cravado voltaria a bloquear no dia em
     * que alguem mudasse a porta -- com o mesmo sintoma obscuro de CSP.
     *
     * O host que importa e o que o NAVEGADOR usa, e nao REVERB_HOST: dentro do
     * Docker o servidor se chama "reverb", mas o navegador chega por localhost.
     * Por isso a preferencia e VITE_REVERB_HOST, que e justamente o valor
     * entregue ao frontend.
     *
     * Devolve lista vazia quando o broadcasting esta desligado
     * (BROADCAST_CONNECTION=null, que foi o padrao do projeto por meses): sem
     * WebSocket, nao ha o que liberar.
     *
     * @return list<string>
     */
    private function origensDoWebsocket(): array
    {
        if (config('broadcasting.default') !== 'reverb') {
            return [];
        }

        $porta = (int) env('VITE_REVERB_PORT', config('broadcasting.connections.reverb.options.port', 8080));

        if ($porta <= 0) {
            return [];
        }

        $hosts = array_unique(array_filter([
            env('VITE_REVERB_HOST'),
            'localhost',
            '127.0.0.1',
        ]));

        $origens = [];

        foreach ($hosts as $host) {
            foreach (['ws', 'wss', 'http', 'https'] as $esquema) {
                // http/https tambem: o pusher-js faz um POST de autenticacao de
                // canal privado antes de abrir o socket, e ele sai pelo mesmo
                // host:porta.
                $origens[] = "{$esquema}://{$host}:{$porta}";
            }
        }

        return $origens;
    }
}
