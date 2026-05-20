<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Database\ConnectionSemaphore;
use App\Services\Database\DatabaseCircuitBreaker;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Drop gracioso de tiers de baixa prioridade quando o sistema esta sob carga.
 * Roda antes do ApiRateLimiter, antes de tocar no DB.
 *
 * Regras:
 *  - Tiers `internal`, `admin`, `enterprise`, `webhook`: sempre passam.
 *  - Tiers `public`/`free`/`default` com circuit breaker aberto: 503.
 *  - Tier `public` acima do threshold publico de utilizacao: 503.
 *  - Tiers `free`/`default` acima do threshold free de utilizacao: 503.
 */
class Backpressure
{
    private const TIERS_BYPASS = ['internal', 'admin', 'enterprise', 'webhook'];

    public function __construct(
        private ConnectionSemaphore $semaphore,
        private DatabaseCircuitBreaker $cb,
    ) {}

    public function handle(Request $request, Closure $next, string $tier = 'default'): Response
    {
        if (in_array($tier, self::TIERS_BYPASS, true)) {
            return $next($request);
        }

        $publicThreshold = (float) config('resilience.backpressure.public_drop_threshold', 0.7);
        $freeThreshold = (float) config('resilience.backpressure.free_drop_threshold', 0.9);

        $utilization = $this->semaphore->active() / max(1, $this->semaphore->limit());

        $shouldDrop = match (true) {
            $this->cb->isOpen() && in_array($tier, ['public', 'free', 'default'], true) => true,
            $tier === 'public' && $utilization >= $publicThreshold => true,
            in_array($tier, ['free', 'default'], true) && $utilization >= $freeThreshold => true,
            default => false,
        };

        if ($shouldDrop) {
            return response()->json([
                'error' => 'Service Busy',
                'message' => 'Sistema em alta carga. Tente novamente em breve.',
                'tier' => $tier,
            ], 503, ['Retry-After' => '5']);
        }

        return $next($request);
    }
}
