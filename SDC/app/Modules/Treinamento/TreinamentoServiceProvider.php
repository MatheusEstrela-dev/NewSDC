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
     *
     * NOTA: As rotas do módulo são carregadas via routes/web.php dentro do
     * middleware group 'auth' (que inclui 'web'). NÃO usar loadRoutesFrom()
     * aqui, pois isso registra rotas SEM os middlewares web/auth, causando
     * 403 para todos os usuários. Padrão: mesmo que RatServiceProvider.
     */
    public function boot(): void
    {
        // Rotas carregadas via routes/web.php -> routes/modules/treinamento.php
    }
}
