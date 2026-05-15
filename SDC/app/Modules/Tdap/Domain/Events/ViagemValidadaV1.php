<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Domain\Events;

use App\Core\Events\DomainEvent;

/**
 * Disparado quando uma viagem e aprovada.
 *
 * Consumidores:
 *  - AtualizarProjecaoListener (incrementa contadores no read-model)
 *  - EncerramentoSaga (verifica se foi a ultima viagem prevista do cronograma
 *    e, em caso afirmativo, emite ExecucaoConcluidaV1)
 */
final readonly class ViagemValidadaV1 extends DomainEvent
{
    public function eventName(): string
    {
        return 'tdap.viagem.validada';
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
            'viagem_id'         => $this->metadata['viagem_id']         ?? null,
            'crono_caminhao_id' => $this->metadata['crono_caminhao_id'] ?? null,
            'cronograma_id'     => $this->metadata['cronograma_id']     ?? null,
            'processo_id'       => $this->metadata['processo_id']       ?? null,
            'capacidade_m3'     => $this->metadata['capacidade_m3']     ?? null,
        ];
    }
}
