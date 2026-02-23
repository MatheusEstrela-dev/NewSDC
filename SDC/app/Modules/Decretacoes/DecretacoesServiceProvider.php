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
     *
     * NOTA: As rotas do módulo são carregadas via routes/web.php dentro do
     * middleware group 'auth' (que inclui 'web'). NÃO usar loadRoutesFrom()
     * aqui, pois isso registra rotas SEM os middlewares web/auth, causando
     * 403 para todos os usuários (sessão e autenticação ausentes).
     * Padrão: mesmo que RatServiceProvider.
     */
    public function boot(): void
    {
        // Rotas carregadas via routes/web.php -> routes/modules/decretacoes.php
    }
}
