<?php

declare(strict_types=1);

namespace App\Modules\Tdap;

use App\Core\Outbox\OutboxDispatcher;
use App\Modules\Tdap\Application\Sagas\EncerramentoSaga;
use App\Modules\Tdap\Domain\Events\CronogramaAtivadoV1;
use App\Modules\Tdap\Domain\Events\ExecucaoConcluidaV1;
use App\Modules\Tdap\Domain\Events\ProcessoTdapAbertoV1;
use App\Modules\Tdap\Domain\Events\ProcessoTdapTransitadoV1;
use App\Modules\Tdap\Domain\Events\ViagemValidadaV1;
use App\Modules\Tdap\Listeners\AtualizarProjecaoProcessoListener;
use App\Modules\Tdap\Listeners\EnviarEmailCronogramaListener;
use App\Modules\Tdap\Listeners\RegistrarHistoricoProcessoListener;
use App\Modules\Tdap\Models\Cronograma;
use App\Modules\Tdap\Models\CronoViagem;
use App\Modules\Tdap\Models\Vistoria;
use App\Modules\Tdap\Observers\CronogramaObserver;
use App\Modules\Tdap\Observers\CronoViagemObserver;
use App\Modules\Tdap\Observers\VistoriaObserver;
use App\Modules\Tdap\Services\AtaService;
use App\Modules\Tdap\Services\CaminhaoService;
use App\Modules\Tdap\Services\CronoCaminhaoService;
use App\Modules\Tdap\Services\CronogramaService;
use App\Modules\Tdap\Services\CronoViagemService;
use App\Modules\Tdap\Services\HistoricoService;
use App\Modules\Tdap\Services\LoteService;
use App\Modules\Tdap\Services\PrestadorService;
use App\Modules\Tdap\Services\ProcessoTdapService;
use App\Modules\Tdap\Services\TdapExportBiService;
use App\Modules\Tdap\Services\VistoriaService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

/**
 * TDAP - Transporte e Distribuicao de Agua Potavel.
 *
 * Plano: docs/superpowers/plans/2026-05-11-tdap-migration.md
 *
 * Arquitetura final (apos Fase 6):
 *   - DDD: Request -> DTO -> Controller -> Service -> Model
 *   - Event-driven monolith: Domain Events + Transactional Outbox + Idempotent Listeners
 *   - Cross-module via Outbox (zero acoplamento direto com outros modulos)
 */
class TdapServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Cadastros base (Fase 1)
        $this->app->singleton(PrestadorService::class);
        $this->app->singleton(CaminhaoService::class);

        // Instrumentos contratuais (Fase 2)
        $this->app->singleton(AtaService::class);
        $this->app->singleton(LoteService::class);

        // Cronograma (Fase 3)
        $this->app->singleton(CronogramaService::class);
        $this->app->singleton(CronoCaminhaoService::class);
        $this->app->singleton(CronoViagemService::class);

        // Vistoria (Fase 4)
        $this->app->singleton(VistoriaService::class);

        // Historico + BI Export (Fase 5)
        $this->app->singleton(HistoricoService::class);
        $this->app->singleton(TdapExportBiService::class);

        // Workflow Event-Driven (Fase 6)
        $this->app->singleton(ProcessoTdapService::class);

        // Registra Domain Events no OutboxDispatcher (resolve FQN <-> chave)
        $this->app->extend(OutboxDispatcher::class, function (OutboxDispatcher $dispatcher) {
            return $dispatcher
                ->register('tdap.processo.aberto',     1, ProcessoTdapAbertoV1::class)
                ->register('tdap.processo.transitado', 1, ProcessoTdapTransitadoV1::class)
                ->register('tdap.cronograma.ativado',  1, CronogramaAtivadoV1::class)
                ->register('tdap.viagem.validada',     1, ViagemValidadaV1::class)
                ->register('tdap.execucao.concluida',  1, ExecucaoConcluidaV1::class);
        });
    }

    public function boot(): void
    {
        // Observers (Fase 5) - eventos do Eloquent -> tdap_historicos
        Cronograma::observe(CronogramaObserver::class);
        CronoViagem::observe(CronoViagemObserver::class);
        Vistoria::observe(VistoriaObserver::class);

        // Listeners do Outbox (Fase 6) - via Event::listen
        $this->registrarEventListeners();
    }

    private function registrarEventListeners(): void
    {
        // ProcessoTdapAbertoV1
        Event::listen(ProcessoTdapAbertoV1::class, [AtualizarProjecaoProcessoListener::class, 'handle']);
        Event::listen(ProcessoTdapAbertoV1::class, [RegistrarHistoricoProcessoListener::class, 'handle']);

        // ProcessoTdapTransitadoV1
        Event::listen(ProcessoTdapTransitadoV1::class, [AtualizarProjecaoProcessoListener::class, 'handle']);
        Event::listen(ProcessoTdapTransitadoV1::class, [RegistrarHistoricoProcessoListener::class, 'handle']);

        // CronogramaAtivadoV1
        Event::listen(CronogramaAtivadoV1::class, [EnviarEmailCronogramaListener::class, 'handle']);
        Event::listen(CronogramaAtivadoV1::class, [RegistrarHistoricoProcessoListener::class, 'handle']);

        // ViagemValidadaV1
        Event::listen(ViagemValidadaV1::class, [AtualizarProjecaoProcessoListener::class, 'handle']);
        Event::listen(ViagemValidadaV1::class, [RegistrarHistoricoProcessoListener::class, 'handle']);
        Event::listen(ViagemValidadaV1::class, [EncerramentoSaga::class, 'handle']);
    }
}
