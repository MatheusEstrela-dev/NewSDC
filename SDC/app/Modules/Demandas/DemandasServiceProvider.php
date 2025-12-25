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
