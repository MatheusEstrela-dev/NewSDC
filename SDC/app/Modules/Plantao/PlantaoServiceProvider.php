<?php

declare(strict_types=1);

namespace App\Modules\Plantao;

use App\Modules\Plantao\Application\UseCases\ListPlantoesUseCase;
use App\Modules\Plantao\Domain\Repositories\PlantaoRepositoryInterface;
use App\Modules\Plantao\Infrastructure\Persistence\EloquentPlantaoRepository;
use Illuminate\Support\ServiceProvider;

class PlantaoServiceProvider extends ServiceProvider
{
    /**
     * Register services
     */
    public function register(): void
    {
        // Bind Repository Interface to Eloquent Implementation
        $this->app->bind(
            PlantaoRepositoryInterface::class,
            EloquentPlantaoRepository::class
        );

        // Registrar Use Cases como singletons
        $this->app->singleton(ListPlantoesUseCase::class);
    }

    /**
     * Bootstrap services
     *
     * NOTA: As rotas do módulo são carregadas via routes/web.php dentro do
     * middleware group 'auth' (que inclui 'web'). NÃO usar loadRoutesFrom()
     * aqui, pois isso registra rotas SEM os middlewares web/auth, causando
     * 403 para todos os usuários. Padrão: mesmo que os demais módulos.
     */
    public function boot(): void
    {
        // Rotas carregadas via routes/web.php -> routes/modules/plantao.php
    }
}
