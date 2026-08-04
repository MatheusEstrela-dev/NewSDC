<?php

declare(strict_types=1);

namespace App\Core\Events;

use Illuminate\Support\Str;

/**
 * Base imutavel para Domain Events.
 *
 * Subclasses concretas devem:
 *  - ter sufixo de versao (V1, V2, ...) no nome do arquivo/classe
 *  - declarar campos `readonly` no construtor (PHP 8.1+)
 *  - implementar eventName(), eventVersion(), payload()
 *
 * Eventos sao publicados via Transactional Outbox (App\Core\Outbox).
 * Listeners cross-module DEVEM estender App\Core\Events\IdempotentListener.
 */
abstract readonly class DomainEvent
{
    public function __construct(
        public string $eventId,
        public string $aggregateType,
        public string $aggregateId,
        public \DateTimeImmutable $occurredAt,
        /** @var array<string, mixed> */
        public array $metadata = [],
    ) {}

    abstract public function eventName(): string;

    abstract public function eventVersion(): int;

    /**
     * @return array<string, mixed>
     */
    abstract public function payload(): array;

    /**
     * UUIDv7: o id do evento vira chave em outbox_events e processed_events,
     * e a ordenacao temporal embutida mantem os inserts sequenciais.
     */
    public static function newId(): string
    {
        return (string) Str::uuid7();
    }

    /**
     * Helper para reidratar evento a partir do registro do outbox.
     *
     * @param  array<string, mixed>  $row
     */
    public static function fromOutbox(array $row): static
    {
        return new static(
            eventId:       (string) $row['id'],
            aggregateType: (string) $row['aggregate_type'],
            aggregateId:   (string) $row['aggregate_id'],
            occurredAt:    new \DateTimeImmutable((string) $row['occurred_at']),
            metadata:      is_array($row['metadata'] ?? null) ? $row['metadata'] : [],
        );
    }
}
