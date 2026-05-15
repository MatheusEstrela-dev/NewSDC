<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Domain\Events;

use App\Core\Events\DomainEvent;

/**
 * Disparado quando um ProcessoTdap e aberto pelo municipio (estado RASCUNHO).
 *
 * Consumidores:
 *  - AtualizarProjecaoListener (cria linha em tdap_processo_projecoes)
 *  - RegistrarHistoricoListener
 *  - NotificarSwimlaneCedecListener (alerta a CEDEC para iniciar habilitacao)
 */
final readonly class ProcessoTdapAbertoV1 extends DomainEvent
{
    public function eventName(): string
    {
        return 'tdap.processo.aberto';
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
            'numero'        => $this->metadata['numero']        ?? null,
            'municipio_id'  => $this->metadata['municipio_id']  ?? null,
            'aberto_por'    => $this->metadata['aberto_por']    ?? null,
            'contexto'      => $this->metadata['contexto']      ?? null,
        ];
    }
}
