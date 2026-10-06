<?php

declare(strict_types=1);

namespace App\Modules\Pae\Domain\Events;

use App\Core\Events\DomainEvent;

final readonly class CcpaeEmitidoV1 extends DomainEvent
{
    public function eventName(): string
    {
        return 'pae.ccpae.emitido';
    }

    public function eventVersion(): int
    {
        return 1;
    }

    public function payload(): array
    {
        return [
            'protocolo_id' => $this->metadata['protocolo_id'] ?? null,
            'ccpae_id' => $this->metadata['ccpae_id'] ?? null,
            'codigo' => $this->metadata['codigo'] ?? null,
            'actor_user_id' => $this->metadata['actor_user_id'] ?? null,
        ];
    }
}
