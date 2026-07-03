<?php

declare(strict_types=1);

namespace App\Support\Octane;

use App\Support\Database\CoroutineDatabaseManager;
use App\Support\Database\SwoolePdoPool;
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
                'pgsql' => $this->pgsqlPool(),
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
                $pool = $resolved instanceof SwoolePdoPool ? $resolved : null;
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
            'configured_size' => (int) env('SWOOLE_PG_POOL_SIZE', 16),
            'configured_timeout_seconds' => (float) env('SWOOLE_PG_POOL_TIMEOUT', 3.0),
            'bound' => app()->bound('swoole.pgsql.pool'),
            'active' => $pool instanceof SwoolePdoPool,
            'manager' => $manager ? $manager::class : null,
            'manager_coroutine_aware' => $manager instanceof CoroutineDatabaseManager,
            'capacity' => $pool?->capacity(),
            'created' => $pool?->created(),
            'available' => $pool?->available(),
            'timeout_seconds' => $pool?->timeout(),
            'error' => $error,
        ];
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
