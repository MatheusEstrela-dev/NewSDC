<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Domain\Events;

use App\Core\Events\DomainEvent;

final readonly class VistoriaConcluidaV1 extends DomainEvent
{
    public function eventName(): string
    {
        return 'cisterna.vistoria.concluida';
    }

    public function eventVersion(): int
    {
        return 1;
    }

    public function payload(): array
    {
        return $this->metadata;
    }
}
