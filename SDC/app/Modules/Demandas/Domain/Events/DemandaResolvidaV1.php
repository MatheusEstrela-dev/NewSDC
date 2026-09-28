<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Domain\Events;

use App\Core\Events\DomainEvent;

final readonly class DemandaResolvidaV1 extends DomainEvent
{
    public function __construct(
        string $eventId,
        string $aggregateId,
        \DateTimeImmutable $occurredAt,
        public int $resolvidoPorId,
        public ?int $responsavelId = null,
        array $metadata = []
    ) {
        parent::__construct(
            eventId: $eventId,
            aggregateType: 'demanda',
            aggregateId: $aggregateId,
            occurredAt: $occurredAt,
            metadata: $metadata
        );
    }

    public static function create(
        int $demandaId,
        int $resolvidoPorId,
        ?int $responsavelId = null
    ): self {
        return new self(
            eventId: self::newId(),
            aggregateId: (string) $demandaId,
            occurredAt: new \DateTimeImmutable(),
            resolvidoPorId: $resolvidoPorId,
            responsavelId: $responsavelId
        );
    }

    public function eventName(): string
    {
        return 'demanda.resolvida';
    }

    public function eventVersion(): int
    {
        return 1;
    }

    public function payload(): array
    {
        return [
            'resolvido_por_id' => $this->resolvidoPorId,
            'responsavel_id' => $this->responsavelId,
        ];
    }
}
