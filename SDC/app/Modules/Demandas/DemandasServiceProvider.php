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
     */
    public function boot(): void
    {
        // Carregar rotas do módulo
        $routesPath = base_path('routes/modules/demandas.php');
        if (file_exists($routesPath)) {
            $this->loadRoutesFrom($routesPath);
        }

        // Carregar migrations
        $this->loadMigrationsFrom(database_path('migrations'));

        // TODO: Registrar observers para eventos de Task
        // Task::observe(TaskObserver::class);

        // TODO: Registrar policies
        // Gate::policy(Task::class, TaskPolicy::class);
    }
}
