<?php

namespace App\Providers;

use App\Support\Database\SwoolePdoPool;
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
    }

    public function boot(): void
    {
        if (!$this->isRunningInOctane()) {
            return;
        }

        $this->app['events']->listen(WorkerStarting::class, function () {
            $this->warmCaches();
            $this->bootSwoolePdoPool();
        });

        $this->app['events']->listen(RequestReceived::class, function () {
            $this->resetRequestState();
        });

        $this->app['events']->listen(RequestTerminated::class, function () {
            $this->flushRequestState();
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
        } catch (\Throwable $e) {
            // Pool e otimizacao opcional; nunca derruba o worker se falhar.
        }
    }

    protected function isSwoole(): bool
    {
        return extension_loaded('swoole')
            && config('octane.server') === 'swoole';
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
