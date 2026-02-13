<?php

declare(strict_types=1);

namespace App\Modules\Demandas;

use App\Modules\Demandas\Domain\Repositories\TaskRepositoryInterface;
use App\Modules\Demandas\Infrastructure\Persistence\EloquentTaskRepository;
use Illuminate\Support\ServiceProvider;

/**
 * Service Provider: Módulo Demandas
 *
 * Registra todas as dependências do módulo (Repository bindings, etc)
 */
class DemandasServiceProvider extends ServiceProvider
{
    /**
     * Register services
     */
    public function register(): void
    {
        // Bind Repository Interface to Eloquent Implementation
        $this->app->bind(
            TaskRepositoryInterface::class,
            EloquentTaskRepository::class
        );

        // Registrar Use Cases (singleton para melhor performance)
        $this->app->singleton(\App\Modules\Demandas\Application\UseCases\CreateTaskUseCase::class);
        $this->app->singleton(\App\Modules\Demandas\Application\UseCases\ShowTaskUseCase::class);
        $this->app->singleton(\App\Modules\Demandas\Application\UseCases\UpdateTaskUseCase::class);
        $this->app->singleton(\App\Modules\Demandas\Application\UseCases\DeleteTaskUseCase::class);
        $this->app->singleton(\App\Modules\Demandas\Application\UseCases\AddCommentUseCase::class);
        $this->app->singleton(\App\Modules\Demandas\Application\UseCases\AssignTaskUseCase::class);
        $this->app->singleton(\App\Modules\Demandas\Application\UseCases\ChangeTaskStatusUseCase::class);
        $this->app->singleton(\App\Modules\Demandas\Application\UseCases\ListTasksUseCase::class);
        $this->app->singleton(\App\Modules\Demandas\Application\UseCases\GetTaskStatisticsUseCase::class);
    }

    /**
     * Bootstrap services
     *
     * NOTA: As rotas do módulo são carregadas via routes/web.php dentro do
     * middleware group 'auth' (que inclui 'web'). NÃO usar loadRoutesFrom()
     * aqui, pois isso registra rotas SEM os middlewares web/auth, causando
     * 403 para todos os usuários. Padrão: mesmo que RatServiceProvider.
     */
    public function boot(): void
    {
        // Rotas carregadas via routes/web.php -> routes/modules/demandas.php
    }
}
