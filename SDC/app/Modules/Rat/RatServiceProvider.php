<?php

namespace App\Modules\Rat;

use App\Core\Actions\Services\ActionConfigService;
use App\Modules\Rat\Application\Services\RatService;
use App\Modules\Rat\Config\RatActionsConfig;
use App\Modules\Rat\Domain\Repositories\RatRepositoryInterface;
use App\Modules\Rat\Infrastructure\Repositories\RatRepository;
use App\Modules\Rat\Services\RatAttachmentService;
use App\Modules\Rat\Services\RatExportService;
use App\Modules\Rat\Services\RatFilterService;
use App\Modules\Rat\Services\RatProtocoloService;
use App\Modules\Rat\Services\RatStatisticsService;
use App\Modules\Rat\Services\RatWriteService;
use App\Services\Rat\RatAuditService;
use App\Services\Rat\RatBiService;
use App\Services\Rat\RatHistoricoService;
use App\Services\Rat\RatNovoService;
use App\Services\Rat\RatOcorrenciaService;
use App\Services\Rat\RatRecursoService;
use App\Services\Rat\RatRelatoService;
use App\Services\Rat\RatTrackingService;
use Illuminate\Support\ServiceProvider;

class RatServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Estrutura Modular (UUID/Rats table)
        $this->app->bind(
            RatRepositoryInterface::class,
            RatRepository::class
        );

        // Application Services
        $this->app->singleton(RatService::class);
        $this->app->singleton(RatProtocoloService::class);
        $this->app->singleton(RatAttachmentService::class);
        $this->app->singleton(RatWriteService::class);
        $this->app->singleton(RatExportService::class);
        $this->app->singleton(RatStatisticsService::class);
        $this->app->singleton(RatFilterService::class);

        // Nova estrutura (RatOcorrencia + relatos polimórficos)
        $this->app->singleton(RatOcorrenciaService::class);
        $this->app->singleton(RatRelatoService::class);
        $this->app->singleton(RatNovoService::class);
        $this->app->singleton(RatAuditService::class);
        $this->app->singleton(RatBiService::class);
        $this->app->singleton(RatRecursoService::class);
        $this->app->singleton(RatTrackingService::class);

        // Histórico dedicado por ocorrência (timeline estruturada)
        $this->app->singleton(RatHistoricoService::class);
    }

    public function boot(): void
    {
        $this->registerModuleActions();
    }

    /**
     * Registra as configuracoes de acoes do modulo RAT.
     */
    private function registerModuleActions(): void
    {
        if ($this->app->bound(ActionConfigService::class)) {
            $actionConfigService = $this->app->make(ActionConfigService::class);
            $actionConfigService->registerModule(new RatActionsConfig());
        }
    }
}

