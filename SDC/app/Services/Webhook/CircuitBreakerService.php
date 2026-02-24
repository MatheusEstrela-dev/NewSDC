<?php

namespace App\Services\Webhook;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Circuit Breaker para webhooks de saida
 * Protege o sistema contra falhas em cascata quando servicos externos estao instáveis
 *
 * Estados:
 * - closed: funcionamento normal
 * - open: circuito aberto, nao permite requisicoes
 * - half-open: permite uma requisicao de teste
 */
class CircuitBreakerService
{
    protected int $failureThreshold;
    protected int $resetTimeout;
    protected string $cachePrefix = 'circuit_breaker:';

    public function __construct()
    {
        $this->failureThreshold = config('webhooks.circuit_breaker.threshold', 5);
        $this->resetTimeout = config('webhooks.circuit_breaker.timeout', 60);
    }

    /**
     * Verifica se o circuito esta aberto para um servico
     */
    public function isOpen(string $service): bool
    {
        $state = $this->getState($service);

        if ($state === 'open') {
            $openedAt = Cache::get($this->cachePrefix . $service . ':opened_at');

            if ($openedAt && (time() - $openedAt) >= $this->resetTimeout) {
                $this->setState($service, 'half-open');
                Log::channel('circuit_breaker')->info('Circuit breaker transitioning to half-open', [
                    'service' => $service,
                ]);
                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * Registra sucesso - pode fechar o circuito se estava em half-open
     */
    public function recordSuccess(string $service): void
    {
        $state = $this->getState($service);

        if ($state === 'half-open') {
            $this->reset($service);
        }

        $this->decrementFailures($service);
    }

    /**
     * Registra falha - pode abrir o circuito se exceder threshold
     */
    public function recordFailure(string $service): void
    {
        $failures = $this->incrementFailures($service);

        if ($failures >= $this->failureThreshold) {
            $this->trip($service);
        }
    }

    /**
     * Abre o circuito (trip)
     */
    protected function trip(string $service): void
    {
        $this->setState($service, 'open');
        Cache::put(
            $this->cachePrefix . $service . ':opened_at',
            time(),
            $this->resetTimeout * 2
        );

        Log::channel('circuit_breaker')->warning('Circuit breaker tripped (OPEN)', [
            'service' => $service,
            'failures' => $this->getFailures($service),
            'timeout_seconds' => $this->resetTimeout,
        ]);
    }

    /**
     * Reseta o circuito para estado fechado
     */
    protected function reset(string $service): void
    {
        Cache::forget($this->cachePrefix . $service . ':state');
        Cache::forget($this->cachePrefix . $service . ':failures');
        Cache::forget($this->cachePrefix . $service . ':opened_at');

        Log::channel('circuit_breaker')->info('Circuit breaker reset (CLOSED)', [
            'service' => $service,
        ]);
    }

    /**
     * Obtem estado atual do circuito
     */
    protected function getState(string $service): string
    {
        return Cache::get($this->cachePrefix . $service . ':state', 'closed');
    }

    /**
     * Define estado do circuito
     */
    protected function setState(string $service, string $state): void
    {
        Cache::put(
            $this->cachePrefix . $service . ':state',
            $state,
            $this->resetTimeout * 3
        );
    }

    /**
     * Obtem contador de falhas
     */
    protected function getFailures(string $service): int
    {
        return (int) Cache::get($this->cachePrefix . $service . ':failures', 0);
    }

    /**
     * Incrementa contador de falhas
     */
    protected function incrementFailures(string $service): int
    {
        $key = $this->cachePrefix . $service . ':failures';
        $failures = $this->getFailures($service) + 1;
        Cache::put($key, $failures, $this->resetTimeout * 3);
        return $failures;
    }

    /**
     * Decrementa contador de falhas
     */
    protected function decrementFailures(string $service): void
    {
        $failures = $this->getFailures($service);
        if ($failures > 0) {
            Cache::put(
                $this->cachePrefix . $service . ':failures',
                $failures - 1,
                $this->resetTimeout * 3
            );
        }
    }

    /**
     * Obtem status de todos os circuitos (para health check)
     */
    public function getStatus(string $service): array
    {
        return [
            'service' => $service,
            'state' => $this->getState($service),
            'failures' => $this->getFailures($service),
            'threshold' => $this->failureThreshold,
            'reset_timeout' => $this->resetTimeout,
        ];
    }
}
