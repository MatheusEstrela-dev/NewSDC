<?php

declare(strict_types=1);

namespace App\Modules\Compdec;

use App\Modules\Compdec\Application\UseCases\CreateOrgaoUseCase;
use App\Modules\Compdec\Application\UseCases\GetHierarquiaOrgaoUseCase;
use App\Modules\Compdec\Application\UseCases\GetOrgaoStatisticsUseCase;
use App\Modules\Compdec\Application\UseCases\ListOrgaosUseCase;
use App\Modules\Compdec\Application\UseCases\UpdateOrgaoUseCase;
use App\Modules\Compdec\Application\UseCases\VincularUsuarioUseCase;
use App\Modules\Compdec\Domain\Repositories\OrgaoRepositoryInterface;
use App\Modules\Compdec\Infrastructure\Persistence\EloquentOrgaoRepository;
use Illuminate\Support\ServiceProvider;

class CompdecServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind Repository Interface → Implementação
        $this->app->bind(
            OrgaoRepositoryInterface::class,
            EloquentOrgaoRepository::class
        );

        // Registrar Use Cases como Singletons (performance)
        $this->app->singleton(CreateOrgaoUseCase::class);
        $this->app->singleton(UpdateOrgaoUseCase::class);
        $this->app->singleton(ListOrgaosUseCase::class);
        $this->app->singleton(VincularUsuarioUseCase::class);
        $this->app->singleton(GetHierarquiaOrgaoUseCase::class);
        $this->app->singleton(GetOrgaoStatisticsUseCase::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Carregar rotas do módulo
        $routesPath = base_path('routes/modules/compdec.php');
        if (file_exists($routesPath)) {
            $this->loadRoutesFrom($routesPath);
        }

        // Carregar migrations
        $this->loadMigrationsFrom(database_path('migrations'));
    }
}
