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

        // Registrar Use Cases (singleton para melhor performance)
        $this->app->singleton(\App\Modules\Decretacoes\Application\UseCases\CreateProcessoUseCase::class);
        $this->app->singleton(\App\Modules\Decretacoes\Application\UseCases\ShowProcessoUseCase::class);
        $this->app->singleton(\App\Modules\Decretacoes\Application\UseCases\UpdateProcessoUseCase::class);
        $this->app->singleton(\App\Modules\Decretacoes\Application\UseCases\DeleteProcessoUseCase::class);
        $this->app->singleton(\App\Modules\Decretacoes\Application\UseCases\ListProcessosUseCase::class);
        $this->app->singleton(\App\Modules\Decretacoes\Application\UseCases\UpdateDadosDesastreUseCase::class);
        $this->app->singleton(\App\Modules\Decretacoes\Application\UseCases\GetStatisticsUseCase::class);
        $this->app->singleton(\App\Modules\Decretacoes\Application\UseCases\ExportProcessosUseCase::class);
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

        // TODO: Registrar observers para eventos de Processo
        // Processo::observe(ProcessoObserver::class);

        // TODO: Registrar policies
        // Gate::policy(Processo::class, ProcessoPolicy::class);

        // TODO: Registrar event listeners
        // Event::listen(ProcessoCriado::class, NotificarRedecListener::class);
        // Event::listen(ProcessoCriado::class, SincronizarHexagonListener::class);
    }
}
