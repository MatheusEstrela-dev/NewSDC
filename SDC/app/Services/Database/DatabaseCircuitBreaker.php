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

    public function __construct(
        private int $timeoutThreshold = 5,
        private int $windowSeconds = 60,
        private int $resetSeconds = 30,
    ) {}

    public function isOpen(): bool
    {
        $state = $this->state();

        if ($state === 'open') {
            $openedAt = (int) Cache::get(self::KEY_OPENED_AT, 0);
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
        return (string) Cache::get(self::KEY_STATE, 'closed');
    }

    public function recordTimeout(): void
    {
        $count = (int) Cache::get(self::KEY_TIMEOUTS, 0) + 1;
        Cache::put(self::KEY_TIMEOUTS, $count, $this->windowSeconds);

        if ($count >= $this->timeoutThreshold) {
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
        Cache::put(self::KEY_OPENED_AT, time(), $this->resetSeconds * 3);

        Log::warning('Database circuit breaker tripped (OPEN)', [
            'timeouts_in_window' => Cache::get(self::KEY_TIMEOUTS),
            'reset_in_seconds' => $this->resetSeconds,
        ]);
    }

    private function close(): void
    {
        Cache::forget(self::KEY_STATE);
        Cache::forget(self::KEY_TIMEOUTS);
        Cache::forget(self::KEY_OPENED_AT);

        Log::info('Database circuit breaker reset (CLOSED)');
    }

    private function setState(string $state): void
    {
        Cache::put(self::KEY_STATE, $state, $this->resetSeconds * 3);
    }
}
