<?php

declare(strict_types=1);

namespace App\Modules\Inmet;

use App\Modules\Inmet\Domain\Repositories\EstacaoRepositoryInterface;
use App\Modules\Inmet\Infrastructure\Persistence\EloquentEstacaoRepository;
use App\Modules\Inmet\Infrastructure\ExternalServices\InmetApiClient;
use App\Modules\Inmet\Application\UseCases\GetLeiturasAtuaisUseCase;
use App\Modules\Inmet\Application\UseCases\GetEstatisticasUseCase;
use Illuminate\Support\ServiceProvider;

class InmetServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind Repository Interface → Implementação
        $this->app->bind(
            EstacaoRepositoryInterface::class,
            EloquentEstacaoRepository::class
        );

        // Singleton do API Client (reutiliza conexão)
        $this->app->singleton(InmetApiClient::class);

        // Singletons dos Use Cases (performance)
        $this->app->singleton(GetLeiturasAtuaisUseCase::class);
        $this->app->singleton(GetEstatisticasUseCase::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Carregar rotas do módulo
        $routesPath = base_path('routes/modules/inmet.php');
        if (file_exists($routesPath)) {
            $this->loadRoutesFrom($routesPath);
        }

        // Carregar migrations
        $this->loadMigrationsFrom(database_path('migrations'));
    }
}
