<?php

namespace App\Providers;

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
