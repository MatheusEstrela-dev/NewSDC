<?php

declare(strict_types=1);

namespace App\Database;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Guarda de budget de queries por request. Sinaliza N+1 cedo via DB::listen.
 *
 * - Acima de warn_at: log warning com ultima SQL (suspeita).
 * - Acima de fail_at: log error (confirmado).
 *
 * Reset por request via Octane RequestReceived listener (em AppServiceProvider).
 */
class QueryBudgetGuard
{
    private int $count = 0;
    private bool $bound = false;

    public function __construct(
        private int $warnAt = 30,
        private int $failAt = 100,
    ) {}

    public function bind(): void
    {
        if ($this->bound) {
            return;
        }
        $this->bound = true;

        DB::listen(function (QueryExecuted $event): void {
            $this->count++;

            if ($this->count === $this->warnAt + 1) {
                Log::warning('Query budget warning crossed', [
                    'count' => $this->count,
                    'last_sql' => $event->sql,
                ]);
            }

            if ($this->count === $this->failAt + 1) {
                Log::error('Query budget exceeded - possivel N+1', [
                    'count' => $this->count,
                    'last_sql' => $event->sql,
                ]);
            }
        });
    }

    public function reset(): void
    {
        $this->count = 0;
    }

    public function count(): int
    {
        return $this->count;
    }
}
