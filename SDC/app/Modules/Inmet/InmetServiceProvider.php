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
     *
     * NOTA: As rotas do módulo são carregadas via routes/web.php dentro do
     * middleware group 'auth' (que inclui 'web'). NÃO usar loadRoutesFrom()
     * aqui, pois isso registra rotas SEM os middlewares web/auth, causando
     * 403 para todos os usuários. Padrão: mesmo que RatServiceProvider.
     */
    public function boot(): void
    {
        // Rotas carregadas via routes/web.php -> routes/modules/inmet.php
    }
}
