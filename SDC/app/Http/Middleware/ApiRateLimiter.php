<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware de Rate Limiting inteligente
 * Suporta até 100k usuários simultâneos com diferentes níveis
 */
class ApiRateLimiter
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $tier = 'default'): Response
    {
        $user = $request->user();

        // Define limites por tier
        $limits = $this->getLimitsByTier($tier);

        // Key única por usuário ou IP
        $key = $user ? "user:{$user->id}:tier:{$tier}" : "ip:{$request->ip()}:tier:{$tier}";

        // Aplica rate limiting
        $executed = RateLimiter::attempt(
            $key,
            $limits['max_attempts'],
            function() {},
            $limits['decay_seconds']
        );

        if (!$executed) {
            return response()->json([
                'message' => 'Too many requests. Please slow down.',
                'retry_after' => RateLimiter::availableIn($key),
                'tier' => $tier,
                'limit' => $limits['max_attempts'],
                'window' => $limits['decay_seconds']
            ], 429);
        }

        // Adiciona headers informativos
        $response = $next($request);

        $response->headers->set('X-RateLimit-Limit', $limits['max_attempts']);
        $response->headers->set('X-RateLimit-Remaining', RateLimiter::remaining($key, $limits['max_attempts']));
        $response->headers->set('X-RateLimit-Reset', now()->addSeconds($limits['decay_seconds'])->timestamp);

        return $response;
    }

    /**
     * Define limites de requisições por tier
     * Preparado para 100k usuários simultâneos
     */
    private function getLimitsByTier(string $tier): array
    {
        return match($tier) {
            // Tier público - limitado
            'public' => [
                'max_attempts' => 60,      // 60 requisições
                'decay_seconds' => 60,     // por minuto
            ],

            // Tier padrão - usuários autenticados
            'default' => [
                'max_attempts' => 300,     // 300 requisições
                'decay_seconds' => 60,     // por minuto
            ],

            // Tier premium - usuários pagos
            'premium' => [
                'max_attempts' => 1000,    // 1000 requisições
                'decay_seconds' => 60,     // por minuto
            ],

            // Tier enterprise - grandes volumes
            'enterprise' => [
                'max_attempts' => 5000,    // 5000 requisições
                'decay_seconds' => 60,     // por minuto
            ],

            // Tier webhooks - para integrações
            'webhook' => [
                'max_attempts' => 10000,   // 10000 requisições
                'decay_seconds' => 60,     // por minuto
            ],

            // Tier interno - sem limites rígidos
            'internal' => [
                'max_attempts' => 100000,  // 100k requisições
                'decay_seconds' => 60,     // por minuto
            ],

            default => [
                'max_attempts' => 100,
                'decay_seconds' => 60,
            ],
        };
    }
}
