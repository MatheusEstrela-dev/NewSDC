<?php

declare(strict_types=1);

namespace App\Modules\Pmda;

use Illuminate\Support\ServiceProvider;

class PmdaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Services como singletons (stateless). Preenchidos fase a fase:
        // $this->app->singleton(\App\Modules\Pmda\Services\PmdaPlanoService::class);
    }

    public function boot(): void
    {
        // Observers registrados na Fase 1:
        // \App\Modules\Pmda\Models\PmdaPlano::observe(\App\Modules\Pmda\Observers\PmdaPlanoObserver::class);
    }
}
