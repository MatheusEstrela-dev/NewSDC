<?php

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
            // Log de segurança para rate limit excedido
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

        $response->headers->set('X-RateLimit-Limit', $limits['max_attempts']);
        $response->headers->set('X-RateLimit-Remaining', max(0, $limits['max_attempts'] - $limitCheck['current_usage']));
        $response->headers->set('X-RateLimit-Reset', now()->addSeconds($limits['decay_seconds'])->timestamp);
        $response->headers->set('X-RateLimit-Cost', $cost);

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
            // Verifica se Redis está disponível
            if (!class_exists('Redis') && !class_exists('Predis\Client')) {
                return ['allowed' => true, 'current_usage' => 0, 'retry_after' => 0];
            }

            $exists = Redis::exists($key);
            $currentUsage = Redis::incrbyfloat($key, $cost);

            if (!$exists) {
                Redis::expire($key, $limits['decay_seconds']);
            }

            if ($currentUsage > $limits['max_attempts']) {
                $retryAfter = Redis::ttl($key);

                return [
                    'allowed' => false,
                    'current_usage' => $currentUsage,
                    'retry_after' => $retryAfter > 0 ? $retryAfter : $limits['decay_seconds'],
                ];
            }

            return [
                'allowed' => true,
                'current_usage' => $currentUsage,
                'retry_after' => 0,
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
                return ['allowed' => false, 'current_usage' => 0, 'retry_after' => 10];
            }

            return ['allowed' => true, 'current_usage' => 0, 'retry_after' => 0];
        }
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

        try {
            $current = (int) Redis::incr('rate_limit:global:per_second');
            if ($current === 1) {
                Redis::expire('rate_limit:global:per_second', 1);
            }

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
