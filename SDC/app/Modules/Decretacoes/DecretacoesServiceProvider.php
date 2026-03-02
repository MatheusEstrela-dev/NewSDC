<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes;

use App\Modules\Decretacoes\Services\DesastreService;
use App\Modules\Decretacoes\Services\HexagonIntegrationService;
use App\Modules\Decretacoes\Services\ProcessoService;
use Illuminate\Support\ServiceProvider;

/**
 * Service Provider: Modulo Decretacoes
 *
 * Registra todas as dependencias do modulo de reconhecimento de desastres
 * Arquitetura: Request -> DTO -> Controller -> Service -> Model
 */
class DecretacoesServiceProvider extends ServiceProvider
{
    /**
     * Register services
     */
    public function register(): void
    {
        // Registrar Services consolidados (singleton para melhor performance)
        $this->app->singleton(HexagonIntegrationService::class);
        $this->app->singleton(DesastreService::class);
        $this->app->singleton(ProcessoService::class, function ($app) {
            return new ProcessoService(
                $app->make(HexagonIntegrationService::class)
            );
        });
    }

    /**
     * Bootstrap services
     *
     * NOTA: As rotas do modulo sao carregadas via routes/web.php dentro do
     * middleware group 'auth' (que inclui 'web'). NAO usar loadRoutesFrom()
     * aqui, pois isso registra rotas SEM os middlewares web/auth, causando
     * 403 para todos os usuarios (sessao e autenticacao ausentes).
     */
    public function boot(): void
    {
        // Rotas carregadas via routes/web.php -> routes/modules/decretacoes.php
    }
}
