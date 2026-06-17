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

    /** @var (callable():?\Redis)|null Resolve a conexao no momento do uso. */
    private $redisResolver;

    public function __construct(
        ?callable $redisResolver,
        private int $limit,
        private int $waitMs = 50,
        private int $maxWaitMs = 2000,
    ) {
        $this->redisResolver = $redisResolver;
    }

    /**
     * Resolve a conexao Redis por-chamada (NAO captura no construtor). Sob
     * Swoole+pool, cada coroutine recebe a sua conexao; capturar uma unica vez
     * num singleton compartilharia o socket entre coroutines concorrentes.
     */
    private function redis(): ?Redis
    {
        if ($this->redisResolver === null) {
            return null;
        }

        try {
            return ($this->redisResolver)();
        } catch (\Throwable $e) {
            return null;
        }
    }

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
        $redis = $this->redis();
        if ($redis === null) {
            return true;
        }

        $start = microtime(true);

        do {
            $current = (int) $redis->eval(
                self::LUA_INCR_WITH_TTL,
                [self::KEY_ACTIVE, (string) self::TTL_SECONDS],
                1
            );

            if ($current <= $this->limit) {
                $redis->sAdd(self::KEY_OWNERS, $owner);
                $redis->expire(self::KEY_OWNERS, self::TTL_SECONDS);
                return true;
            }

            $redis->decr(self::KEY_ACTIVE);
            usleep($this->waitMs * 1000);
        } while ((microtime(true) - $start) * 1000 < $this->maxWaitMs);

        return false;
    }

    public function release(string $owner): void
    {
        $redis = $this->redis();
        if ($redis === null) {
            return;
        }

        if ((int) $redis->sRem(self::KEY_OWNERS, $owner) === 1) {
            $redis->decr(self::KEY_ACTIVE);
        }
    }

    public function active(): int
    {
        $redis = $this->redis();
        if ($redis === null) {
            return 0;
        }

        return (int) $redis->get(self::KEY_ACTIVE);
    }

    public function limit(): int
    {
        return $this->limit;
    }
}
