<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Domain\Events;

use App\Core\Events\DomainEvent;

final readonly class ComentarioAdicionadoV1 extends DomainEvent
{
    public function __construct(
        string $eventId,
        string $aggregateId,
        \DateTimeImmutable $occurredAt,
        public int $comentarioId,
        public int $autorId,
        public bool $interno,
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
        int $comentarioId,
        int $autorId,
        bool $interno
    ): self {
        return new self(
            eventId: self::newId(),
            aggregateId: (string) $demandaId,
            occurredAt: new \DateTimeImmutable(),
            comentarioId: $comentarioId,
            autorId: $autorId,
            interno: $interno
        );
    }

    public function eventName(): string
    {
        return 'demanda.comentario_adicionado';
    }

    public function eventVersion(): int
    {
        return 1;
    }

    public function payload(): array
    {
        return [
            'comentario_id' => $this->comentarioId,
            'autor_id' => $this->autorId,
            'interno' => $this->interno,
        ];
    }
}
