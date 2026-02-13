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
     *
     * NOTA: As rotas do módulo são carregadas via routes/web.php dentro do
     * middleware group 'auth' (que inclui 'web'). NÃO usar loadRoutesFrom()
     * aqui, pois isso registra rotas SEM os middlewares web/auth, causando
     * 403 para todos os usuários. Padrão: mesmo que RatServiceProvider.
     */
    public function boot(): void
    {
        // Rotas carregadas via routes/web.php -> routes/modules/compdec.php
    }
}
