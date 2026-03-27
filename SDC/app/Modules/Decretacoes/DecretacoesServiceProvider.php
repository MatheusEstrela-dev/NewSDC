<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes;

use App\Modules\Decretacoes\Models\Processo;
use App\Modules\Decretacoes\Observers\ProcessoObserver;
use App\Modules\Decretacoes\Services\DesastreDataService;
use App\Modules\Decretacoes\Services\EntradaProcessoService;
use App\Modules\Decretacoes\Services\HexagonIntegrationService;
use App\Modules\Decretacoes\Services\ProcessoExportService;
use App\Modules\Decretacoes\Services\ProcessoQueryService;
use App\Modules\Decretacoes\Services\ProcessoStatsService;
use Illuminate\Support\ServiceProvider;

/**
 * Service Provider: Modulo Decretacoes
 */
class DecretacoesServiceProvider extends ServiceProvider
{
    /**
     * Register services
     */
    public function register(): void
    {
        // Carrega modelos e DTOs consolidados (nao seguem padrao PSR-4 de 1 arquivo por classe)
        require_once __DIR__ . '/Models/DecretacoesModels.php';
        require_once __DIR__ . '/DTO/DecretacoesDTO.php';

        $this->app->singleton(HexagonIntegrationService::class);
        $this->app->singleton(DesastreDataService::class);
        $this->app->singleton(ProcessoQueryService::class);
        $this->app->singleton(ProcessoStatsService::class);
        $this->app->singleton(ProcessoExportService::class);

        $this->app->singleton(EntradaProcessoService::class, function ($app) {
            return new EntradaProcessoService(
                $app->make(HexagonIntegrationService::class),
                $app->make(ProcessoQueryService::class),
                $app->make(ProcessoStatsService::class),
                $app->make(ProcessoExportService::class)
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
        Processo::observe(ProcessoObserver::class);
    }
}
