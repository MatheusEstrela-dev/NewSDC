<?php

declare(strict_types=1);

namespace App\Modules\Pae;

use App\Core\Outbox\OutboxDispatcher;
use App\Modules\Pae\Console\VerificarNotificacoesPae;
use App\Modules\Pae\Domain\Events\FormularioValidadoV1;
use App\Modules\Pae\Domain\Events\ParecerConcluidoV1;
use App\Modules\Pae\Domain\Events\ProtocoloEnviadoV1;
use App\Modules\Pae\Domain\Events\RevisaoAceitaV1;
use App\Modules\Pae\Domain\Events\CcpaeEmitidoV1;
use App\Modules\Pae\Domain\Guards\ExigeEmissaoCcpae;
use App\Modules\Pae\Domain\Guards\ExigeAdmissibilidade;
use App\Modules\Pae\Domain\Workflows\PaeProtocoloWorkflow;
use App\Modules\Pae\Services\EmpreendimentoApiService;
use App\Modules\Pae\Services\PaeFormularioService;
use App\Modules\Pae\Services\PaeNotificacaoService;
use App\Modules\Pae\Services\PaePrazoService;
use App\Modules\Pae\Services\PaeProtocoloService;
use App\Modules\Pae\Services\PaeComunicacaoService;
use App\Modules\Pae\Services\PaeDcoService;
use App\Modules\Pae\Services\PaeEvacuacaoService;
use App\Support\Calendario\CalendarioDiasUteis;
use Illuminate\Support\ServiceProvider;

/**
 * PAE - Plano de Acao de Emergencia.
 *
 * Provider criado junto com a instrumentacao de Domain Events: ate entao o
 * modulo funcionava sem provider, resolvendo os services por auto-wiring. Ele
 * passou a ser necessario porque OutboxDispatcher::reidratar() so sabe
 * reconstruir um evento cujo par nome@versao esteja registrado; sem o mapa
 * abaixo, persist() grava a linha e o worker falha ao despachar, deixando os
 * eventos presos em outbox_events.
 */
class PaeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PaeProtocoloService::class);
        $this->app->singleton(PaeFormularioService::class);
        $this->app->singleton(PaeNotificacaoService::class);
        $this->app->singleton(EmpreendimentoApiService::class);
        $this->app->singleton(CalendarioDiasUteis::class, fn () => CalendarioDiasUteis::padrao());
        $this->app->singleton(PaePrazoService::class);
        $this->app->singleton(PaeCcpaeService::class);
        $this->app->singleton(PaeComunicacaoService::class);
        $this->app->singleton(PaeDcoService::class);
        $this->app->singleton(PaeEvacuacaoService::class);

        // Guards da maquina de estados: cada subprojeto acrescenta o seu na tag,
        // sem mexer no workflow (o B traz o de admissibilidade).
        $this->app->tag([ExigeEmissaoCcpae::class, ExigeAdmissibilidade::class], 'pae.guardas_transicao');
        $this->app->when(PaeProtocoloWorkflow::class)
            ->needs('$guardas')
            ->giveTagged('pae.guardas_transicao');
        $this->app->singleton(PaeProtocoloWorkflow::class);

        $this->app->extend(OutboxDispatcher::class, function (OutboxDispatcher $dispatcher) {
            return $dispatcher
                ->register('pae.protocolo.enviado', 1, ProtocoloEnviadoV1::class)
                ->register('pae.formulario.validado', 1, FormularioValidadoV1::class)
                ->register('pae.revisao.aceita', 1, RevisaoAceitaV1::class)
                ->register('pae.parecer.concluido', 1, ParecerConcluidoV1::class)
                ->register('pae.ccpae.emitido', 1, CcpaeEmitidoV1::class);
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([VerificarNotificacoesPae::class]);
        }
    }
}
