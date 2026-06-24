<?php

declare(strict_types=1);

namespace App\Modules\Pmda;

use App\Modules\Pmda\Models\PmdaPlano;
use App\Modules\Pmda\Observers\PmdaPlanoObserver;
use App\Modules\Pmda\Services\ComunidadeService;
use App\Modules\Pmda\Services\PmdaCopiaService;
use App\Modules\Pmda\Services\PmdaPlanoService;
use App\Modules\Pmda\Services\RepresentanteService;
use Illuminate\Support\ServiceProvider;

class PmdaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PmdaPlanoService::class);
        $this->app->singleton(PmdaCopiaService::class);
        $this->app->singleton(ComunidadeService::class);
        $this->app->singleton(RepresentanteService::class);
    }

    public function boot(): void
    {
        PmdaPlano::observe(PmdaPlanoObserver::class);
    }
}
