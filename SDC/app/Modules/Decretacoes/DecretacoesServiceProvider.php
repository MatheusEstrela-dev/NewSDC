<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes;

use App\Modules\Decretacoes\Models\Processo;
use App\Modules\Decretacoes\Observers\ProcessoObserver;
use App\Modules\Decretacoes\Services\DesastreDataService;
use App\Modules\Decretacoes\Services\EntradaProcessoService;
use App\Modules\Decretacoes\Services\HexagonIntegrationService;
use App\Modules\Decretacoes\Services\ProcessoExportBIService;
use App\Modules\Decretacoes\Services\ProcessoQueryService;
use App\Modules\Decretacoes\Services\ProcessoStatsService;
use App\Modules\Decretacoes\Services\RedecService;
use Illuminate\Support\ServiceProvider;
use Laravel\Octane\Events\RequestReceived;

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
        // Modelos e DTOs consolidados (varias classes por arquivo) sao resolvidos
        // pelo `classmap` do composer.json, como os do Tdap e do Pmda. Antes eram
        // carregados aqui por require_once: funcionava, mas custava carregar os dois
        // arquivos em toda requisicao e enchia o `dump-autoload` de 17 avisos PSR-4.
        $this->app->singleton(HexagonIntegrationService::class);
        $this->app->singleton(DesastreDataService::class);
        $this->app->singleton(ProcessoQueryService::class);
        $this->app->singleton(ProcessoStatsService::class);
        $this->app->singleton(ProcessoExportBIService::class);

        $this->app->singleton(EntradaProcessoService::class, function ($app) {
            return new EntradaProcessoService(
                $app->make(HexagonIntegrationService::class),
                $app->make(ProcessoQueryService::class),
                $app->make(ProcessoStatsService::class),
                $app->make(ProcessoExportBIService::class)
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

        // Catalogo de REDECs: o memo de RedecService e estatico e, sob
        // Octane/Swoole, o worker atravessa centenas de requisicoes. Sem este
        // reset, cadastrar uma REDEC nova em `dec_redecs` so apareceria depois
        // do worker reciclar - o cache compartilhado e invalidado por
        // clearCache(), mas o memo de cada processo nao.
        if (class_exists(RequestReceived::class)) {
            $this->app['events']->listen(
                RequestReceived::class,
                fn () => RedecService::flushMemo()
            );
        }
    }
}
