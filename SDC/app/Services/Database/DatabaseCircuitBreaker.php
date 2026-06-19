<?php

declare(strict_types=1);

namespace App\Services\Database;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Circuit breaker que observa timeouts de query no PostgreSQL.
 * Reusa o padrao closed/open/half-open de App\Services\Webhook\CircuitBreakerService.
 *
 * Quando aberto, middlewares de backpressure devolvem 503 nas rotas
 * heavy/expensive, mantendo healthcheck e tiers internos passando.
 */
class DatabaseCircuitBreaker
{
    private const PREFIX = 'db_cb:';
    private const KEY_STATE = self::PREFIX.'state';
    private const KEY_TIMEOUTS = self::PREFIX.'timeouts';
    private const KEY_OPENED_AT = self::PREFIX.'opened_at';

    private string $localState = 'closed';

    private int $localTimeouts = 0;

    private int $localTimeoutWindowStartedAt = 0;

    private int $localOpenedAt = 0;

    public function __construct(
        private int $timeoutThreshold = 5,
        private int $windowSeconds = 60,
        private int $resetSeconds = 30,
    ) {}

    public function isOpen(): bool
    {
        $state = $this->state();

        if ($state === 'open') {
            $openedAt = $this->localOpenedAt;
            if ($openedAt && (time() - $openedAt) >= $this->resetSeconds) {
                $this->setState('half-open');
                return false;
            }
            return true;
        }

        return false;
    }

    public function state(): string
    {
        return $this->localState;
    }

    public function recordTimeout(): void
    {
        $now = time();
        if ($this->localTimeoutWindowStartedAt === 0
            || ($now - $this->localTimeoutWindowStartedAt) >= $this->windowSeconds) {
            $this->localTimeoutWindowStartedAt = $now;
            $this->localTimeouts = 0;
        }

        $this->localTimeouts++;

        try {
            Cache::put(self::KEY_TIMEOUTS, $this->localTimeouts, $this->windowSeconds);
        } catch (\Throwable) {
            // Observabilidade best-effort: o circuit breaker nao pode derrubar
            // worker por falha/concorrencia de Redis.
        }

        if ($this->localTimeouts >= $this->timeoutThreshold) {
            $this->trip();
        }
    }

    public function recordSuccess(): void
    {
        if ($this->state() === 'half-open') {
            $this->close();
        }
    }

    private function trip(): void
    {
        $this->setState('open');
        $this->localOpenedAt = time();

        try {
            Cache::put(self::KEY_OPENED_AT, $this->localOpenedAt, $this->resetSeconds * 3);
        } catch (\Throwable) {
        }

        Log::warning('Database circuit breaker tripped (OPEN)', [
            'timeouts_in_window' => $this->localTimeouts,
            'reset_in_seconds' => $this->resetSeconds,
        ]);
    }

    private function close(): void
    {
        $this->localState = 'closed';
        $this->localTimeouts = 0;
        $this->localTimeoutWindowStartedAt = 0;
        $this->localOpenedAt = 0;

        try {
            Cache::forget(self::KEY_STATE);
            Cache::forget(self::KEY_TIMEOUTS);
            Cache::forget(self::KEY_OPENED_AT);
        } catch (\Throwable) {
        }

        Log::info('Database circuit breaker reset (CLOSED)');
    }

    private function setState(string $state): void
    {
        $this->localState = $state;

        try {
            Cache::put(self::KEY_STATE, $state, $this->resetSeconds * 3);
        } catch (\Throwable) {
        }
    }
}
