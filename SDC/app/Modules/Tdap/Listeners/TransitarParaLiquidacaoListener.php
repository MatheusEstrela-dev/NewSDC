<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Listeners;

use App\Core\Events\DomainEvent;
use App\Core\Events\IdempotentListener;
use App\Modules\Tdap\Domain\Events\ExecucaoConcluidaV1;
use App\Modules\Tdap\DTOs\TransicaoProcessoDTO;
use App\Modules\Tdap\Enums\EstadoProcesso;
use App\Modules\Tdap\Models\ProcessoTdap;
use App\Modules\Tdap\Services\ProcessoTdapService;
use Illuminate\Support\Facades\Log;

/**
 * Fecha o ciclo da execucao: EM_EXECUCAO -> LIQUIDACAO_PENDENTE.
 *
 * A EncerramentoSaga ja emitia ExecucaoConcluidaV1 quando todas as viagens
 * previstas de um ProcessoTdap eram validadas, e o proprio docblock do evento
 * anunciava "Consumidor principal: TransitarParaLiquidacaoListener" -- mas o
 * listener nunca existiu e o evento morria no outbox. O processo ficava parado
 * em EM_EXECUCAO para sempre, esperando alguem transitar na mao.
 *
 * A transicao passa pelo ProcessoTdapService, e nao por um update direto, para
 * herdar o guard da maquina de estados, o lock e a emissao de
 * ProcessoTdapTransitadoV1 (que atualiza a projecao do kanban).
 */
class TransitarParaLiquidacaoListener extends IdempotentListener
{
    public function __construct(
        private readonly ProcessoTdapService $processos,
    ) {}

    protected function execute(DomainEvent $event): void
    {
        if (! $event instanceof ExecucaoConcluidaV1) {
            return;
        }

        $processoId = (string) ($event->payload()['processo_id'] ?? '');
        if ($processoId === '') {
            return;
        }

        $processo = ProcessoTdap::find($processoId);
        if (! $processo) {
            Log::warning('ProcessoTdap nao encontrado ao concluir execucao', [
                'processo_id' => $processoId,
                'event_id'    => $event->eventId,
            ]);

            return;
        }

        // Processo que ja saiu de EM_EXECUCAO (transitado na mao, por exemplo)
        // nao e erro: o evento so chegou depois. Sai em silencio.
        if ($processo->estado !== EstadoProcesso::EmExecucao) {
            return;
        }

        [, $erro] = $this->processos->transitar(
            $processoId,
            new TransicaoProcessoDTO(
                estadoAlvo: EstadoProcesso::LiquidacaoPendente,
                motivo: sprintf(
                    'Execucao concluida automaticamente: %s viagem(ns) validada(s), %s m3 entregues.',
                    $event->payload()['total_viagens_validadas'] ?? '?',
                    $event->payload()['total_agua_entregue_m3'] ?? '?',
                ),
            ),
        );

        if ($erro !== null) {
            // A maquina de estados recusou. Logar e desistir: repetir o evento
            // nao muda a resposta, e o listener e idempotente por event_id.
            Log::warning('Transicao automatica para liquidacao recusada', [
                'processo_id' => $processoId,
                'event_id'    => $event->eventId,
                'motivo'      => $erro,
            ]);
        }
    }
}
