<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Logging\ActivityLogger;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware de Rate Limiting CONTEXTUAL
 *
 * Sistema robusto baseado no papiro.md que considera:
 * 1. Plano do usuário (Free, Pro, Enterprise)
 * 2. Custo da rota (rotas pesadas custam mais "créditos")
 * 3. Redis com operações atômicas (INCR, EXPIRE)
 *
 * Preparado para 100k+ usuários simultâneos
 */
class ApiRateLimiter
{
    /**
     * Custo de créditos por tipo de rota
     */
    private const ROUTE_COSTS = [
        // Rotas muito pesadas (processamento intenso)
        'heavy' => 10,

        // Rotas pesadas (relatórios, exports)
        'expensive' => 5,

        // Rotas normais (CRUD)
        'normal' => 1,

        // Rotas leves (health check, status)
        'light' => 0.5,
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $tier = 'default'): Response
    {
        // 0. Bucket global por segundo: protege contra tempestade legítima
        // de tiers de baixa prioridade (publico, free) sob carga.
        $globalDecision = $this->checkGlobalBucket($tier);
        if ($globalDecision !== null) {
            return $globalDecision;
        }

        $user = $request->user();

        // 1. Define o limite baseado no Plano (Contexto do Cliente)
        $limits = $this->getLimitsByTier($tier, $user);

        // 2. Define o custo da rota (Contexto da Rota)
        $cost = $this->getRouteCost($request);

        // 3. Key única por usuário ou IP
        $key = 'rate_limit:' . ($user ? "user:{$user->id}" : "ip:{$request->ip()}") . ":tier:{$tier}";

        // 4. Verifica rate limit usando Redis (operações atômicas)
        $limitCheck = $this->checkRateLimit($key, $cost, $limits, $tier);

        if (!$limitCheck['allowed']) {
            // Log de seguranca apenas na requisicao que cruzou o limite. O
            // ActivityLogger e sincrono (debug_backtrace + Redis + arquivo); sob
            // enxurrada, logar cada recusa fazia a defesa custar mais que o
            // ataque. Uma linha por chave por janela preserva o sinal.
            if ($limitCheck['first_rejection'] ?? false) {
                ActivityLogger::logSecurity(
                    event: 'rate_limit_exceeded',
                    data: [
                        'user_id' => $user?->id,
                        'ip' => $request->ip(),
                        'tier' => $tier,
                        'limit' => $limits['max_attempts'],
                        'cost' => $cost,
                        'current_usage' => $limitCheck['current_usage'],
                        'path' => $request->path(),
                    ],
                    severity: 'warning'
                );
            }

            return response()->json([
                'error' => 'Rate Limit Exceeded',
                'message' => 'Too many requests. Please slow down.',
                'retry_after_seconds' => $limitCheck['retry_after'],
                'tier' => $tier,
                'limit' => $limits['max_attempts'],
                'window_seconds' => $limits['decay_seconds'],
                'cost_per_request' => $cost,
            ], 429);
        }

        // 5. Adiciona headers informativos (Padrão de mercado)
        $response = $next($request);

        // Symfony ResponseHeaderBag::set() exige array|string|null no valor;
        // os limites/custos sao int/float, entao convertemos para string.
        $response->headers->set('X-RateLimit-Limit', (string) $limits['max_attempts']);
        $response->headers->set('X-RateLimit-Remaining', (string) max(0, $limits['max_attempts'] - $limitCheck['current_usage']));
        $response->headers->set('X-RateLimit-Reset', (string) now()->addSeconds($limits['decay_seconds'])->timestamp);
        $response->headers->set('X-RateLimit-Cost', (string) $cost);

        return $response;
    }

    /**
     * Verifica rate limit usando Redis (operações atômicas)
     *
     * Baseado no papiro.md - usa INCR e EXPIRE do Redis
     */
    private function checkRateLimit(string $key, float $cost, array $limits, string $tier = 'default'): array
    {
        try {
            // Sem Redis: fail-open — rate limit desativado, libera a requisicao.
            if (!config('resilience.redis_enabled', true)) {
                return ['allowed' => true, 'current_usage' => 0, 'retry_after' => 0];
            }

            // Verifica se Redis está disponível
            if (!class_exists('Redis') && !class_exists('Predis\Client')) {
                return ['allowed' => true, 'current_usage' => 0, 'retry_after' => 0];
            }

            // Um eval no lugar de exists + incrbyfloat + expire + ttl: quatro
            // round-trips viravam um. Alem do custo, a sequencia solta tinha uma
            // janela em que o processo morria entre o INCR e o EXPIRE e deixava a
            // chave SEM expiracao -- o contador daquele usuario nunca mais zerava
            // e ele ficava em 429 permanente.
            [$usage, $ttl, $primeiraRecusa] = $this->evalRateLimit(
                $key,
                $cost,
                $limits['decay_seconds'],
                (float) $limits['max_attempts'],
            );

            if ($usage > $limits['max_attempts']) {
                return [
                    'allowed' => false,
                    'current_usage' => $usage,
                    'retry_after' => $ttl > 0 ? $ttl : $limits['decay_seconds'],
                    'first_rejection' => $primeiraRecusa,
                ];
            }

            return [
                'allowed' => true,
                'current_usage' => $usage,
                'retry_after' => 0,
                'first_rejection' => false,
            ];

        } catch (\Throwable $e) {
            \Log::error('Redis error in rate limiter', [
                'error' => $e->getMessage(),
                'key' => $key,
                'tier' => $tier,
            ]);

            // Fail-closed: tiers de baixa prioridade sao recusados quando Redis cai;
            // tiers altos passam (continuidade para usuarios autenticados criticos).
            $bypassOnError = ['pro', 'premium', 'enterprise', 'internal', 'admin', 'webhook'];
            if ((bool) config('resilience.rate_limit.fail_closed', true)
                && !in_array($tier, $bypassOnError, true)) {
                return ['allowed' => false, 'current_usage' => 0, 'retry_after' => 10, 'first_rejection' => false];
            }

            return ['allowed' => true, 'current_usage' => 0, 'retry_after' => 0, 'first_rejection' => false];
        }
    }

    /**
     * Passo atomico do limitador por chave.
     *
     * Devolve [uso, ttl, primeiraRecusa]. `primeiraRecusa` marca a requisicao que
     * CRUZOU o limite nesta janela -- so ela merece log de seguranca: as demais
     * repetem o mesmo evento e, numa enxurrada, transformariam a recusa (que
     * existe para custar barato) numa tempestade de escrita.
     *
     * Numeros voltam como string porque o Lua converte numero em inteiro no
     * retorno, o que truncaria o custo fracionario das rotas leves (0.5).
     *
     * @return array{0: float, 1: int, 2: bool}
     */
    private function evalRateLimit(string $key, float $cost, int $decaySeconds, float $maxAttempts): array
    {
        $lua = <<<'LUA'
            local existia = redis.call('EXISTS', KEYS[1])
            local anterior = 0

            if existia == 1 then
                anterior = tonumber(redis.call('GET', KEYS[1])) or 0
            end

            local uso = tonumber(redis.call('INCRBYFLOAT', KEYS[1], ARGV[1]))

            if existia == 0 then
                redis.call('EXPIRE', KEYS[1], ARGV[2])
            end

            local limite = tonumber(ARGV[3])
            local primeira = 0

            if uso > limite and anterior <= limite then
                primeira = 1
            end

            return { tostring(uso), tostring(redis.call('TTL', KEYS[1])), primeira }
        LUA;

        $resultado = Redis::connection()->eval(
            $lua,
            1,
            $key,
            (string) $cost,
            (string) $decaySeconds,
            (string) $maxAttempts,
        );

        return [
            (float) ($resultado[0] ?? 0),
            (int) ($resultado[1] ?? $decaySeconds),
            (int) ($resultado[2] ?? 0) === 1,
        ];
    }

    /**
     * Bucket global por segundo: protege o sistema contra picos legitimos
     * de tiers de baixa prioridade. Tiers altos sempre passam.
     */
    private function checkGlobalBucket(string $tier): ?Response
    {
        $tiersBypass = ['internal', 'admin', 'enterprise', 'webhook'];
        if (in_array($tier, $tiersBypass, true)) {
            return null;
        }

        // Sem Redis: bucket global desativado — nao bloqueia.
        if (!config('resilience.redis_enabled', true)) {
            return null;
        }

        try {
            // INCR e EXPIRE no mesmo script: soltos, a morte do processo entre
            // os dois deixava a chave do segundo corrente sem expiracao nenhuma
            // e todo tier baixo tomava 503 para sempre.
            $lua = <<<'LUA'
                local v = redis.call('INCR', KEYS[1])

                if v == 1 then
                    redis.call('EXPIRE', KEYS[1], 1)
                end

                return v
            LUA;

            $current = (int) Redis::connection()->eval($lua, 1, 'rate_limit:global:per_second');

            $threshold = (int) config('resilience.rate_limit.global_per_second', 1500);
            if ($current > $threshold && in_array($tier, ['public', 'free', 'default'], true)) {
                return response()->json([
                    'error' => 'Service Busy',
                    'message' => 'Capacidade global atingida; tente em alguns segundos.',
                ], 503, ['Retry-After' => '5']);
            }
        } catch (\Throwable $e) {
            \Log::error('Redis error in global rate limiter', [
                'error' => $e->getMessage(),
                'tier' => $tier,
            ]);

            // Fail-closed em queda do Redis: tiers baixos sao recusados.
            if ((bool) config('resilience.rate_limit.fail_closed', true)
                && in_array($tier, ['public', 'free', 'default'], true)) {
                return response()->json([
                    'error' => 'Service Degraded',
                    'message' => 'Rate limit indisponivel; tente em breve.',
                ], 503, ['Retry-After' => '10']);
            }
        }

        return null;
    }

    /**
     * Define o custo da rota baseado no padrão da URL
     */
    private function getRouteCost(Request $request): float
    {
        $path = $request->path();
        $method = $request->method();

        // Rotas MUITO PESADAS (custo 10)
        if (str_contains($path, 'export') ||
            str_contains($path, 'relatorio') ||
            str_contains($path, 'report')) {
            return self::ROUTE_COSTS['heavy'];
        }

        // Rotas PESADAS (custo 5)
        if (str_contains($path, 'dashboard') ||
            str_contains($path, 'analytics') ||
            str_contains($path, 'batch') ||
            str_contains($path, 'import')) {
            return self::ROUTE_COSTS['expensive'];
        }

        // Rotas LEVES (custo 0.5)
        if (str_contains($path, 'health') ||
            str_contains($path, 'status') ||
            str_contains($path, 'ping') ||
            $method === 'GET' && str_contains($path, 'list')) {
            return self::ROUTE_COSTS['light'];
        }

        // Rotas NORMAIS (custo 1)
        return self::ROUTE_COSTS['normal'];
    }

    /**
     * Define limites de requisições por tier
     * Preparado para 100k usuários simultâneos
     *
     * Considera o plano do usuário (armazenado em $user->plan ou role)
     */
    private function getLimitsByTier(string $tier, $user = null): array
    {
        // Se o usuário tem um plano específico, usa ele
        if ($user && isset($user->plan)) {
            $tier = $user->plan;
        }

        return match($tier) {
            // Tier público - limitado (usuários não autenticados)
            'public' => [
                'max_attempts' => 60,      // 60 créditos
                'decay_seconds' => 60,     // por minuto
            ],

            // Tier free - usuários cadastrados gratuitos
            'free', 'default' => [
                'max_attempts' => 300,     // 300 créditos
                'decay_seconds' => 60,     // por minuto
            ],

            // Tier pro - usuários profissionais
            'pro', 'premium' => [
                'max_attempts' => 1000,    // 1000 créditos
                'decay_seconds' => 60,     // por minuto
            ],

            // Tier enterprise - grandes organizações
            'enterprise' => [
                'max_attempts' => 10000,   // 10k créditos
                'decay_seconds' => 60,     // por minuto
            ],

            // Tier webhook - para integrações assíncronas
            'webhook' => [
                'max_attempts' => 50000,   // 50k créditos
                'decay_seconds' => 60,     // por minuto
            ],

            // Tier interno - sem limites rígidos
            'internal', 'admin' => [
                'max_attempts' => 100000,  // 100k créditos
                'decay_seconds' => 60,     // por minuto
            ],

            default => [
                'max_attempts' => 100,
                'decay_seconds' => 60,
            ],
        };
    }
}
