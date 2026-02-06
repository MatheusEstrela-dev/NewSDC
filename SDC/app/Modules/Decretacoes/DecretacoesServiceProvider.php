<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes;

use App\Modules\Decretacoes\Domain\Repositories\ProcessoRepositoryInterface;
use App\Modules\Decretacoes\Infrastructure\Persistence\EloquentProcessoRepository;
use Illuminate\Support\ServiceProvider;

/**
 * Service Provider: Módulo Decretações
 *
 * Registra todas as dependências do módulo de reconhecimento de desastres
 * Seguindo padrão Always-to-DTO
 */
class DecretacoesServiceProvider extends ServiceProvider
{
    /**
     * Register services
     */
    public function register(): void
    {
        // Bind Repository Interface to Eloquent Implementation
        $this->app->bind(
            ProcessoRepositoryInterface::class,
            EloquentProcessoRepository::class
        );

        // Registrar UseCases ativos (singleton para melhor performance)
        $this->app->singleton(\App\Modules\Decretacoes\Application\UseCases\ListProcessosUseCase::class);
        $this->app->singleton(\App\Modules\Decretacoes\Application\UseCases\GetDecretacoesStatisticsUseCase::class);
        $this->app->singleton(\App\Modules\Decretacoes\Application\UseCases\GetProcessoFormDataUseCase::class);
        $this->app->singleton(\App\Modules\Decretacoes\Application\UseCases\GetProcessoShowUseCase::class);

        // Registrar Services (singleton)
        $this->app->singleton(\App\Modules\Decretacoes\Application\Services\ProcessoService::class);
    }

    /**
     * Bootstrap services
     */
    public function boot(): void
    {
        // Carregar rotas do módulo
        $routesPath = base_path('routes/modules/decretacoes.php');
        if (file_exists($routesPath)) {
            $this->loadRoutesFrom($routesPath);
        }

        // Carregar migrations
        $this->loadMigrationsFrom(database_path('migrations'));
    }
}
