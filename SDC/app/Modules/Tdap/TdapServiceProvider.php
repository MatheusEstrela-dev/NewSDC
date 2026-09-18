<?php

declare(strict_types=1);

namespace App\Modules\Tdap;

use App\Core\Outbox\OutboxDispatcher;
use App\Modules\Tdap\Domain\Events\CronogramaAtivadoV1;
use App\Modules\Tdap\Domain\Events\ViagemValidadaV1;
use App\Modules\Tdap\Listeners\EnviarEmailCronogramaListener;
use App\Modules\Tdap\Listeners\RegistrarHistoricoProcessoListener;
use App\Modules\Tdap\Models\Caminhao;
use App\Modules\Tdap\Models\Cronograma;
use App\Modules\Tdap\Models\CronoViagem;
use App\Modules\Tdap\Models\Prestador;
use App\Modules\Tdap\Models\Vistoria;
use App\Modules\Tdap\Observers\CaminhaoObserver;
use App\Modules\Tdap\Observers\CronogramaObserver;
use App\Modules\Tdap\Observers\CronoViagemObserver;
use App\Modules\Tdap\Observers\PrestadorObserver;
use App\Modules\Tdap\Observers\VistoriaObserver;
use App\Modules\Tdap\Services\AtaService;
use App\Modules\Tdap\Services\FrotaService;
use App\Modules\Tdap\Services\CronoCaminhaoService;
use App\Modules\Tdap\Services\CronogramaService;
use App\Modules\Tdap\Services\CronoViagemService;
use App\Modules\Tdap\Services\HistoricoService;
use App\Modules\Tdap\Services\LoteService;
use App\Modules\Tdap\Services\PrestadorService;
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
        $this->app->singleton(FrotaService::class);

        // Instrumentos contratuais (Fase 2)
        $this->app->singleton(AtaService::class);
        $this->app->singleton(LoteService::class);

        // Cronograma (Fase 3)
        $this->app->singleton(CronogramaService::class);
        $this->app->singleton(\App\Modules\Tdap\Services\CronogramaComprovanteService::class);
        $this->app->singleton(CronoCaminhaoService::class);
        $this->app->singleton(CronoViagemService::class);

        // Vistoria (Fase 4)
        $this->app->singleton(VistoriaService::class);

        // Historico + BI Export (Fase 5)
        $this->app->singleton(HistoricoService::class);
        $this->app->singleton(TdapExportBiService::class);

        /*
         * Registra Domain Events no OutboxDispatcher (resolve FQN <-> chave).
         *
         * As chaves do modulo Processos (tdap.processo.aberto,
         * tdap.processo.transitado, tdap.execucao.concluida) sairam com ele.
         * Linhas antigas de outbox_events com esses nomes nao reidratam mais:
         * o OutboxDispatcher captura o erro e apenas incrementa a tentativa,
         * entao nada quebra -- mas `event:replay` lanca de fato. Foi conferido
         * que nao ha pendencia dessas chaves antes da remocao.
         */
        $this->app->extend(OutboxDispatcher::class, function (OutboxDispatcher $dispatcher) {
            return $dispatcher
                ->register('tdap.cronograma.ativado', 1, CronogramaAtivadoV1::class)
                ->register('tdap.viagem.validada',    1, ViagemValidadaV1::class);
        });
    }

    public function boot(): void
    {
        // Observers (Fase 5) - eventos do Eloquent -> tdap_historicos
        Cronograma::observe(CronogramaObserver::class);
        CronoViagem::observe(CronoViagemObserver::class);
        Vistoria::observe(VistoriaObserver::class);
        // Prestador e a raiz do modulo: cadastro/ativacao ficavam fora da
        // trilha mesmo com a chave 'prestador' ja mapeada no HistoricoService.
        Prestador::observe(PrestadorObserver::class);
        // Caminhao: mesma historia do prestador, chave 'caminhao' mapeada e
        // nenhum observer -- trocar placa ou desativar veiculo nao deixava
        // rastro, e as duas coisas mudam quem pode rodar.
        Caminhao::observe(CaminhaoObserver::class);

        // Listeners do Outbox (Fase 6) - via Event::listen
        $this->registrarEventListeners();
    }

    private function registrarEventListeners(): void
    {
        // CronogramaAtivadoV1
        Event::listen(CronogramaAtivadoV1::class, [EnviarEmailCronogramaListener::class, 'handle']);
        Event::listen(CronogramaAtivadoV1::class, [RegistrarHistoricoProcessoListener::class, 'handle']);

        // ViagemValidadaV1 (emitido por CronoViagemService::validar na aprovacao)
        Event::listen(ViagemValidadaV1::class, [RegistrarHistoricoProcessoListener::class, 'handle']);
    }
}
