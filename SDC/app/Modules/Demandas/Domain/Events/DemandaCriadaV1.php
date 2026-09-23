<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Domain\Events;

use App\Core\Events\DomainEvent;

final readonly class DemandaCriadaV1 extends DomainEvent
{
    public function __construct(
        string $eventId,
        string $aggregateId,
        \DateTimeImmutable $occurredAt,
        public string $protocolo,
        public string $tipo,
        public string $prioridade,
        public int $solicitanteId,
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
        string $protocolo,
        string $tipo,
        string $prioridade,
        int $solicitanteId
    ): self {
        return new self(
            eventId: self::newId(),
            aggregateId: (string) $demandaId,
            occurredAt: new \DateTimeImmutable(),
            protocolo: $protocolo,
            tipo: $tipo,
            prioridade: $prioridade,
            solicitanteId: $solicitanteId
        );
    }

    public function eventName(): string
    {
        return 'demanda.criada';
    }

    public function eventVersion(): int
    {
        return 1;
    }

    public function payload(): array
    {
        return [
            'protocolo' => $this->protocolo,
            'tipo' => $this->tipo,
            'prioridade' => $this->prioridade,
            'solicitante_id' => $this->solicitanteId,
        ];
    }
}
