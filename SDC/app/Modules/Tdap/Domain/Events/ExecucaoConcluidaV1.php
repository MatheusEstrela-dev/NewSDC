<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Domain\Events;

use App\Core\Events\DomainEvent;

/**
 * Disparado pela EncerramentoSaga quando todas as viagens previstas dos
 * cronogramas vinculados ao ProcessoTdap foram validadas.
 *
 * Consumidor principal:
 *  - TransitarParaLiquidacaoListener: transita o ProcessoTdap de
 *    EM_EXECUCAO -> LIQUIDACAO_PENDENTE
 */
final readonly class ExecucaoConcluidaV1 extends DomainEvent
{
    public function eventName(): string
    {
        return 'tdap.execucao.concluida';
    }

    public function eventVersion(): int
    {
        return 1;
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return [
            'processo_id'              => $this->metadata['processo_id']              ?? null,
            'total_viagens_validadas'  => $this->metadata['total_viagens_validadas']  ?? null,
            'total_agua_entregue_m3'   => $this->metadata['total_agua_entregue_m3']   ?? null,
        ];
    }
}
