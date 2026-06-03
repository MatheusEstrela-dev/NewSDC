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
        private ?Redis $redis,
        private int $limit,
        private int $waitMs = 50,
        private int $maxWaitMs = 2000,
    ) {}

    /**
     * Lua script atomico: INCR + EXPIRE somente na primeira vez (TTL natural).
     * Garante que counter NUNCA fica sem TTL — se o processo morrer entre
     * INCR e EXPIRE no padrao anterior, o counter cresceria indefinidamente
     * causando 503 permanente.
     */
    private const LUA_INCR_WITH_TTL = <<<'LUA'
        local v = redis.call('INCR', KEYS[1])
        if v == 1 then redis.call('EXPIRE', KEYS[1], ARGV[1]) end
        return v
    LUA;

    public function acquire(string $owner): bool
    {
        // Sem Redis: no-op — concede sempre (backpressure desativado).
        if ($this->redis === null) {
            return true;
        }

        $start = microtime(true);

        do {
            $current = (int) $this->redis->eval(
                self::LUA_INCR_WITH_TTL,
                [self::KEY_ACTIVE, (string) self::TTL_SECONDS],
                1
            );

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
        if ($this->redis === null) {
            return;
        }

        if ((int) $this->redis->sRem(self::KEY_OWNERS, $owner) === 1) {
            $this->redis->decr(self::KEY_ACTIVE);
        }
    }

    public function active(): int
    {
        if ($this->redis === null) {
            return 0;
        }

        return (int) $this->redis->get(self::KEY_ACTIVE);
    }

    public function limit(): int
    {
        return $this->limit;
    }
}
