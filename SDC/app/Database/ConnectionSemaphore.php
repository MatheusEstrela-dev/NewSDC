<?php

declare(strict_types=1);

namespace App\Database;

use Redis;

/**
 * Semaforo Redis-backed que limita o numero de requests concorrentes
 * batendo no DB. Funciona em qualquer ambiente: dev, staging, prod,
 * com ou sem PgBouncer externo.
 *
 * Uso:
 *   $owner = (string) Str::uuid();
 *   if (!$semaphore->acquire($owner)) {
 *       return response()->json([...], 503);
 *   }
 *   try {
 *       // processa request
 *   } finally {
 *       $semaphore->release($owner);
 *   }
 */
class ConnectionSemaphore
{
    private const KEY_ACTIVE = 'db:slots:active';
    private const KEY_OWNERS = 'db:slots:owners';
    private const TTL_SECONDS = 60;

    public function __construct(
        private Redis $redis,
        private int $limit,
        private int $waitMs = 50,
        private int $maxWaitMs = 2000,
    ) {}

    public function acquire(string $owner): bool
    {
        $start = microtime(true);

        do {
            $current = (int) $this->redis->incr(self::KEY_ACTIVE);
            $this->redis->expire(self::KEY_ACTIVE, self::TTL_SECONDS);

            if ($current <= $this->limit) {
                $this->redis->sAdd(self::KEY_OWNERS, $owner);
                $this->redis->expire(self::KEY_OWNERS, self::TTL_SECONDS);
                return true;
            }

            $this->redis->decr(self::KEY_ACTIVE);
            usleep($this->waitMs * 1000);
        } while ((microtime(true) - $start) * 1000 < $this->maxWaitMs);

        return false;
    }

    public function release(string $owner): void
    {
        if ((int) $this->redis->sRem(self::KEY_OWNERS, $owner) === 1) {
            $this->redis->decr(self::KEY_ACTIVE);
        }
    }

    public function active(): int
    {
        return (int) $this->redis->get(self::KEY_ACTIVE);
    }

    public function limit(): int
    {
        return $this->limit;
    }
}
