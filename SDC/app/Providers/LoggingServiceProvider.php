<?php

declare(strict_types=1);

namespace App\Providers;

use App\Http\Middleware\LogRequestLifecycle;
use App\Observers\ModelLoggingObserver;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class LoggingServiceProvider extends ServiceProvider
{
    protected array $observedModels = [
        // Core Models
        \App\Models\User::class,

        // Module Models - Adicione seus models aqui
        // \App\Modules\Decretacoes\Models\Processo::class,
        // \App\Modules\AjudaHumanitaria\Models\Beneficiario::class,
    ];

    protected array $excludedFromObserver = [
        \App\Models\Log\Logs::class,
        \App\Models\AuditLog::class,
    ];

    public function register(): void
    {
        $this->app->singleton(ModelLoggingObserver::class);
    }

    public function boot(): void
    {
        $this->registerMiddleware();
        $this->registerModelObservers();
        $this->registerGlobalModelEvents();
    }

    protected function registerMiddleware(): void
    {
        $kernel = $this->app->make(Kernel::class);

        $kernel->pushMiddleware(LogRequestLifecycle::class);
    }

    protected function registerModelObservers(): void
    {
        $observer = $this->app->make(ModelLoggingObserver::class);

        foreach ($this->observedModels as $modelClass) {
            if (class_exists($modelClass) && !in_array($modelClass, $this->excludedFromObserver)) {
                $modelClass::observe($observer);
            }
        }
    }

    protected function registerGlobalModelEvents(): void
    {
        $observer = $this->app->make(ModelLoggingObserver::class);

        Event::listen('eloquent.created: *', function (string $event, array $models) use ($observer) {
            foreach ($models as $model) {
                if ($model instanceof Model && $this->shouldObserve($model)) {
                    $observer->created($model);
                }
            }
        });

        Event::listen('eloquent.updated: *', function (string $event, array $models) use ($observer) {
            foreach ($models as $model) {
                if ($model instanceof Model && $this->shouldObserve($model)) {
                    $observer->updated($model);
                }
            }
        });

        Event::listen('eloquent.deleted: *', function (string $event, array $models) use ($observer) {
            foreach ($models as $model) {
                if ($model instanceof Model && $this->shouldObserve($model)) {
                    $observer->deleted($model);
                }
            }
        });
    }

    protected function shouldObserve(Model $model): bool
    {
        $class = get_class($model);

        if (in_array($class, $this->excludedFromObserver)) {
            return false;
        }

        if (in_array($class, $this->observedModels)) {
            return false;
        }

        return true;
    }
}
