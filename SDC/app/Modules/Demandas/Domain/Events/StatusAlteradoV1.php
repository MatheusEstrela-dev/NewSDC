<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Domain\Events;

use App\Core\Events\DomainEvent;

final readonly class StatusAlteradoV1 extends DomainEvent
{
    public function __construct(
        string $eventId,
        string $aggregateId,
        \DateTimeImmutable $occurredAt,
        public string $statusAnterior,
        public string $statusNovo,
        public ?int $userId,
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
        string $statusAnterior,
        string $statusNovo,
        ?int $userId = null
    ): self {
        return new self(
            eventId: self::newId(),
            aggregateId: (string) $demandaId,
            occurredAt: new \DateTimeImmutable(),
            statusAnterior: $statusAnterior,
            statusNovo: $statusNovo,
            userId: $userId
        );
    }

    public function eventName(): string
    {
        return 'demanda.status_alterado';
    }

    public function eventVersion(): int
    {
        return 1;
    }

    public function payload(): array
    {
        return [
            'status_anterior' => $this->statusAnterior,
            'status_novo' => $this->statusNovo,
            'user_id' => $this->userId,
        ];
    }
}
