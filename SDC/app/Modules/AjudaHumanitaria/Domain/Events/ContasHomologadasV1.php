<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Domain\Events;

use App\Core\Events\DomainEvent;

final readonly class ContasHomologadasV1 extends DomainEvent
{
    public function eventName(): string
    {
        return 'ajuda_humanitaria.contas.homologadas';
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
