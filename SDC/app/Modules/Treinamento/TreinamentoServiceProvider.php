<?php

declare(strict_types=1);

namespace App\Modules\Treinamento;

use App\Modules\Treinamento\Application\UseCases\Treinamento\CreateTreinamentoUseCase;
use App\Modules\Treinamento\Application\UseCases\Treinamento\ListTreinamentosUseCase;
use App\Modules\Treinamento\Application\UseCases\Treinamento\ShowTreinamentoUseCase;
use App\Modules\Treinamento\Domain\Repositories\TreinamentoRepositoryInterface;
use App\Modules\Treinamento\Infrastructure\Persistence\EloquentTreinamentoRepository;
use Illuminate\Support\ServiceProvider;

class TreinamentoServiceProvider extends ServiceProvider
{
    /**
     * Register services
     */
    public function register(): void
    {
        // Bind Repository Interfaces to Eloquent Implementations
        $this->app->bind(
            TreinamentoRepositoryInterface::class,
            EloquentTreinamentoRepository::class
        );

        // Registrar Use Cases como singletons
        $this->app->singleton(CreateTreinamentoUseCase::class);
        $this->app->singleton(ListTreinamentosUseCase::class);
        $this->app->singleton(ShowTreinamentoUseCase::class);
    }

    /**
     * Bootstrap services
     */
    public function boot(): void
    {
        // Carregar rotas do módulo
        $routesPath = base_path('routes/modules/treinamento.php');
        if (file_exists($routesPath)) {
            $this->loadRoutesFrom($routesPath);
        }

        // Carregar migrations
        $this->loadMigrationsFrom(database_path('migrations'));
    }
}
