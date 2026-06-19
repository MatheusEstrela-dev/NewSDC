<?php

namespace App\Providers;

use App\Support\Database\SwoolePdoPool;
use App\Support\Redis\CoroutineRedisManager;
use App\Support\Redis\SwooleRedisPool;
use Illuminate\Support\ServiceProvider;
use Laravel\Octane\Events\RequestReceived;
use Laravel\Octane\Events\RequestTerminated;
use Laravel\Octane\Events\WorkerStarting;

class OctaneServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if (!$this->isRunningInOctane()) {
            return;
        }

        $this->app->singleton('octane.cache', function () {
            return collect();
        });

        // Com hooks Swoole ligados, troca o RedisManager por um coroutine-aware
        // (conexao por cid via pool). Com hooks off, mantem o RedisManager padrao
        // -> comportamento identico ao modo sincrono estavel.
        //
        // O RedisServiceProvider do framework e DEFERRED e bindaria 'redis' ao
        // resolver, sobrescrevendo um singleton nosso. Por isso usamos extend():
        // o extender roda depois que o 'redis' padrao e construido e o substitui,
        // preservando o singleton.
        if ($this->hooksEnabled()) {
            $this->app->extend('redis', function ($manager, $app) {
                $config = $app['config']['database.redis'] ?? [];

                return new CoroutineRedisManager(
                    $app,
                    $config['client'] ?? 'phpredis',
                    $config
                );
            });
            $this->forgetRedisBackedSingletons();

            // Sob hooks, troca o DatabaseManager por um coroutine-aware: a conexao
            // 'pgsql' passa a ser resolvida por-coroutine (PDO do SwoolePdoPool),
            // isolando socket e estado de transacao entre coroutines.
            $this->app->extend('db', function ($manager, $app) {
                return new \App\Support\Database\CoroutineDatabaseManager($app, $app['db.factory']);
            });
        }
    }

    public function boot(): void
    {
        if (!$this->isRunningInOctane()) {
            return;
        }

        $this->app['events']->listen(WorkerStarting::class, function () {
            if ($this->hooksEnabled()) {
                $this->forgetRedisBackedSingletons();
            }
            $this->warmCaches();
            $this->bootSwoolePdoPool();
            $this->bootSwooleRedisPools();
        });

        $this->app['events']->listen(RequestReceived::class, function () {
            $this->resetRequestState();
        });

        $this->app['events']->listen(RequestTerminated::class, function () {
            $this->flushRequestState();
            $this->releaseRedisCoroutine();
            $this->releasePgsqlCoroutine();
        });
    }

    protected function isRunningInOctane(): bool
    {
        return isset($_SERVER['LARAVEL_OCTANE'])
            || env('OCTANE_SERVER') !== null
            || (method_exists($this->app, 'runningInOctane') && $this->app->runningInOctane());
    }

    /**
     * Sob Swoole, cria um pool de conexoes pgsql por worker (uma conexao por
     * coroutine). Inerte em FrankenPHP/RoadRunner: o codigo de aplicacao usa o
     * pool quando ligado (App\Support\Concurrency) ou cai no Eloquent normal.
     * Guardado para nao instanciar pool fora do Swoole.
     */
    protected function bootSwoolePdoPool(): void
    {
        if (! $this->isSwoole()) {
            return;
        }

        try {
            $size = (int) env('SWOOLE_PG_POOL_SIZE', 16);

            $this->app->singleton('swoole.pgsql.pool', fn () => SwoolePdoPool::fromConnection('pgsql', $size));
            if ($this->hooksEnabled()) {
                $this->app->make('swoole.pgsql.pool')->warm();
            }
        } catch (\Throwable $e) {
            // Pool e otimizacao opcional; nunca derruba o worker se falhar.
        }
    }

    /**
     * Cria os pools Redis (default db0, cache db1) por worker quando os hooks
     * Swoole estao ligados. Falha do pool nunca derruba o worker.
     */
    protected function bootSwooleRedisPools(): void
    {
        if (! $this->hooksEnabled()) {
            return;
        }

        try {
            $redis = $this->app->make('redis');
            if (! $redis instanceof CoroutineRedisManager) {
                return;
            }

            $size = (int) env('OCTANE_REDIS_POOL_SIZE', 16);
            $timeout = (float) env('OCTANE_REDIS_POOL_TIMEOUT', 3.0);

            foreach (['default', 'cache'] as $name) {
                $pool = SwooleRedisPool::fromConnection($name, $size, $timeout);
                $pool->warm(); // pre-aquece no boot: evita handshake TLS no burst -> sem timeout de acquire
                $redis->registerPool($name, $pool);
            }
        } catch (\Throwable $e) {
            // Pool e otimizacao opcional; nunca derruba o worker se falhar.
        }
    }

    /**
     * Devolve ao pool as conexoes Redis emprestadas pela coroutine do request.
     */
    protected function releaseRedisCoroutine(): void
    {
        if (! $this->hooksEnabled()) {
            return;
        }

        $cid = \Swoole\Coroutine::getCid();
        if ($cid <= 0) {
            return;
        }

        try {
            $redis = $this->app->make('redis');
            if ($redis instanceof CoroutineRedisManager) {
                $redis->releaseCoroutine($cid);
            }
        } catch (\Throwable $e) {
        }
    }

    /**
     * Devolve ao pool a conexao pgsql emprestada pela coroutine do request
     * (rollback de transacao aberta antes de devolver). Inerte fora de hooks.
     */
    protected function releasePgsqlCoroutine(): void
    {
        if (! $this->hooksEnabled()) {
            return;
        }

        try {
            $db = $this->app->make('db');
            if ($db instanceof \App\Support\Database\CoroutineDatabaseManager) {
                $db->releaseCurrentCoroutine();
            }
        } catch (\Throwable $e) {
        }
    }

    protected function isSwoole(): bool
    {
        return extension_loaded('swoole')
            && config('octane.server') === 'swoole';
    }

    /**
     * Os hooks Swoole estao ligados? (hook_flags != 0). So entao o pool Redis
     * por-coroutine entra em acao; com hooks off, RedisManager padrao.
     */
    protected function hooksEnabled(): bool
    {
        return $this->isSwoole()
            && (int) config('octane.swoole.options.hook_flags', 0) !== 0;
    }

    protected function forgetRedisBackedSingletons(): void
    {
        foreach (['redis', 'cache', 'cache.store', 'cache.psr6'] as $abstract) {
            if ($this->app->resolved($abstract)) {
                $this->app->forgetInstance($abstract);
            }
        }
    }

    protected function warmCaches(): void
    {
        try {
            if (class_exists(\App\Modules\Decretacoes\Services\ProcessoQueryService::class)) {
                $this->app->make(\App\Modules\Decretacoes\Services\ProcessoQueryService::class);
            }
        } catch (\Throwable $e) {
        }
    }

    protected function resetRequestState(): void
    {
        if ($this->app->bound('octane.cache')) {
            $this->app->make('octane.cache')->forget('request_stats');
        }
    }

    protected function flushRequestState(): void
    {
        if ($this->app->bound(\Illuminate\Contracts\Debug\ExceptionHandler::class)) {
            try {
                $handler = $this->app->make(\Illuminate\Contracts\Debug\ExceptionHandler::class);
                if (method_exists($handler, 'forgetExceptions')) {
                    $handler->forgetExceptions();
                }
            } catch (\Throwable $e) {
            }
        }
    }
}
