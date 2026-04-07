<?php

namespace App\Modules\Compdec;

use App\Modules\Compdec\Domain\Repositories\OrgaoRepository;
use App\Modules\Compdec\Domain\Repositories\OrgaoRepositoryInterface;
use App\Modules\Compdec\Models\Orgao;
use App\Modules\Compdec\Services\OrgaoService;
use Illuminate\Support\ServiceProvider;

class CompdecServiceProvider extends ServiceProvider
{
    /**
     * Register services
     */
    public function register(): void
    {
        // Registrar o repository como singleton
        $this->app->singleton(OrgaoRepositoryInterface::class, function ($app) {
            return new OrgaoRepository($app->make(Orgao::class));
        });

        // Registrar o service
        $this->app->singleton(OrgaoService::class, function ($app) {
            return new OrgaoService(
                $app->make(OrgaoRepositoryInterface::class)
            );
        });
    }

    /**
     * Bootstrap services
     */
    public function boot(): void
    {
        // Rutas já carregadas via routes/modules/compdec.php
        // Policies já registradas via AuthServiceProvider
    }
}
