<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Domain\Events;

use App\Core\Events\DomainEvent;

/**
 * Evento generico de transicao de estado do ProcessoTdap.
 *
 * Metadados esperados:
 *   - estado_anterior: string
 *   - estado_novo:     string
 *   - swimlane_atual:  string
 *   - motivo:          ?string
 *   - user_id:         ?int
 */
final readonly class ProcessoTdapTransitadoV1 extends DomainEvent
{
    public function eventName(): string
    {
        return 'tdap.processo.transitado';
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
            'estado_anterior' => $this->metadata['estado_anterior'] ?? null,
            'estado_novo'     => $this->metadata['estado_novo']     ?? null,
            'swimlane_atual'  => $this->metadata['swimlane_atual']  ?? null,
            'motivo'          => $this->metadata['motivo']          ?? null,
            'user_id'         => $this->metadata['user_id']         ?? null,
        ];
    }
}
