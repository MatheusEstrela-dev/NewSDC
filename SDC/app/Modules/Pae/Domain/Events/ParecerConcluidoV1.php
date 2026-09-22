<?php

declare(strict_types=1);

namespace App\Modules\Pae\Domain\Events;

use App\Core\Events\DomainEvent;

/**
 * Analise do protocolo concluida com decisao (aprovado ou reprovado).
 *
 * Emitido por PaeProtocoloService::changeStatus na saida do status ANALISE.
 *
 * Aqui o creditado E o proprio analista que emitiu o parecer: nao ha terceiro
 * a premiar, entao actor_user_id e credited_user_id coincidem por regra e
 * validador_user_id fica nulo -- o ato nao passa por validacao de outrem.
 *
 * `prazo_em` e pae_protocolos.limite_analise; `entregue_em` e o dt_status da
 * tramitacao gravada no mesmo ato. Ambas sao colunas de marco, nao updated_at.
 */
final readonly class ParecerConcluidoV1 extends DomainEvent
{
    public function eventName(): string
    {
        return 'pae.parecer.concluido';
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
            'protocolo_id'     => $this->metadata['protocolo_id']     ?? null,
            'num_protocolo'    => $this->metadata['num_protocolo']    ?? null,
            'ciclo'            => $this->metadata['ciclo']            ?? null,
            'decisao'          => $this->metadata['decisao']          ?? null,
            'status_anterior'  => $this->metadata['status_anterior']  ?? null,
            'actor_user_id'    => $this->metadata['actor_user_id']    ?? null,
            'credited_user_id' => $this->metadata['credited_user_id'] ?? null,
            'prazo_em'         => $this->metadata['prazo_em']         ?? null,
            'entregue_em'      => $this->metadata['entregue_em']      ?? null,
        ];
    }
}
