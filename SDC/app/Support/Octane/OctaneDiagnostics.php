<?php

declare(strict_types=1);

namespace App\Support\Octane;

use App\Support\Database\CoroutineDatabaseManager;
use App\Support\Database\PdoPool;
use App\Support\Database\PdoPoolRegistry;
use App\Support\Redis\CoroutineRedisManager;

class OctaneDiagnostics
{
    public function snapshot(): array
    {
        $hookFlags = (int) config('octane.swoole.options.hook_flags', 0);
        $pgsqlOptions = config('database.connections.pgsql.options', []);

        return [
            'octane' => [
                'server' => config('octane.server'),
                'swoole_extension_loaded' => extension_loaded('swoole'),
                'hook_flags_effective' => $hookFlags,
                'hook_flags_enabled' => $hookFlags !== 0,
                'task_worker_num' => (int) config('octane.swoole.options.task_worker_num', 0),
                'task_enable_coroutine' => (bool) config('octane.swoole.options.task_enable_coroutine', false),
            ],
            'database' => [
                'db_persistent' => (bool) ($pgsqlOptions[\PDO::ATTR_PERSISTENT] ?? false),
            ],
            'pools' => [
                // Mantido por compatibilidade com quem ja le pools.pgsql;
                // o retrato completo, por conexao, esta em pools.database.
                'pgsql' => $this->pgsqlPool(),
                'database' => $this->databasePools(),
                'redis' => $this->redisPools(),
            ],
        ];
    }

    private function pgsqlPool(): array
    {
        $pool = null;
        $error = null;

        if (app()->bound('swoole.pgsql.pool')) {
            try {
                $resolved = app('swoole.pgsql.pool');
                $pool = $resolved instanceof PdoPool ? $resolved : null;
            } catch (\Throwable $e) {
                $error = $e->getMessage();
            }
        }

        $manager = null;
        try {
            $manager = app('db');
        } catch (\Throwable) {
        }

        return [
            // Do config, e nao de env(): com config:cache o env() devolve o
            // default e o diagnostico mentiria sobre o tamanho real.
            'configured_size' => (int) config('database.coroutine_pools.pgsql.size', 16),
            'configured_timeout_seconds' => (float) config('database.coroutine_pools.pgsql.timeout', 3.0),
            'bound' => app()->bound('swoole.pgsql.pool'),
            'active' => $pool instanceof PdoPool,
            'manager' => $manager ? $manager::class : null,
            'manager_coroutine_aware' => $manager instanceof CoroutineDatabaseManager,
            ...PdoPoolRegistry::estado($pool),
            'error' => $error,
        ];
    }

    /**
     * Um bloco por conexao de database.coroutine_pools: o que o config pede e,
     * quando o registry existe (worker Swoole), o estado real de cada pool.
     */
    private function databasePools(): array
    {
        $registry = null;
        $error = null;

        if (app()->bound(PdoPoolRegistry::class)) {
            try {
                $registry = app(PdoPoolRegistry::class);
            } catch (\Throwable $e) {
                $error = $e->getMessage();
            }
        }

        $runtime = $registry instanceof PdoPoolRegistry ? $registry->diagnostico() : [];
        $saida = [];
        foreach ((array) config('database.coroutine_pools', []) as $nome => $pool) {
            $driver = config("database.connections.{$nome}.driver");
            $saida[$nome] = [
                'connection_driver' => $driver,
                'poolable' => $driver === 'pgsql',
                'configured_size' => (int) ($pool['size'] ?? 0),
                'configured_timeout_seconds' => (float) ($pool['timeout'] ?? 0),
                'configured_warm' => (bool) ($pool['warm'] ?? false),
                'configured_read_size' => (int) ($pool['read_size'] ?? 0),
                'registered' => isset($runtime[$nome]),
                'runtime' => $runtime[$nome] ?? null,
                'error' => $error,
            ];
        }

        return $saida;
    }

    private function redisPools(): array
    {
        $manager = null;
        $error = null;

        try {
            $manager = app('redis');
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        }

        return [
            'configured_size' => (int) env('OCTANE_REDIS_POOL_SIZE', 16),
            'configured_timeout_seconds' => (float) env('OCTANE_REDIS_POOL_TIMEOUT', 3.0),
            'manager' => $manager ? $manager::class : null,
            'manager_coroutine_aware' => $manager instanceof CoroutineRedisManager,
            'pools' => $manager instanceof CoroutineRedisManager ? $manager->poolDiagnostics() : [],
            'error' => $error,
        ];
    }
}
