<?php

declare(strict_types=1);

namespace App\Core\Outbox;

use App\Core\Events\DomainEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

/**
 * Worker que despacha eventos pendentes do outbox para o sistema de eventos
 * do Laravel.
 *
 * Postgres-native: SELECT ... FOR UPDATE SKIP LOCKED permite multiplos
 * workers consumindo em paralelo sem contencao (deduplica pelo lock no row).
 *
 * Cada evento e republicado dentro de uma transacao individual: se a
 * publicacao falhar, dispatched_at fica NULL e o worker tenta novamente
 * (com dispatch_attempts + last_error registrados).
 */
class OutboxDispatcher
{
    /** @var array<string, class-string<DomainEvent>> */
    private array $eventMap = [];

    /**
     * Registra o map FQN do evento para sua chave em event_name.
     *
     * @param  class-string<DomainEvent>  $class
     */
    public function register(string $eventName, int $version, string $class): self
    {
        $this->eventMap["{$eventName}@v{$version}"] = $class;

        return $this;
    }

    /**
     * Persiste um Domain Event no outbox.
     */
    public function persist(DomainEvent $event): OutboxEvent
    {
        return OutboxEvent::create([
            'id'             => $event->eventId,
            'event_name'     => $event->eventName(),
            'event_version'  => $event->eventVersion(),
            'aggregate_type' => $event->aggregateType,
            'aggregate_id'   => $event->aggregateId,
            'payload'        => $event->payload(),
            'metadata'       => $event->metadata,
            'occurred_at'    => $event->occurredAt,
        ]);
    }

    /**
     * Consome ate $batch eventos pendentes e publica via Event::dispatch.
     * Retorna numero de eventos efetivamente despachados.
     */
    public function dispatchPending(int $batch = 100): int
    {
        $rows = OutboxEvent::query()
            ->pending()
            ->orderBy('occurred_at')
            ->limit($batch)
            ->lockForUpdate()
            ->get();

        $count = 0;
        foreach ($rows as $row) {
            try {
                DB::transaction(function () use ($row, &$count): void {
                    $event = $this->reidratar($row);
                    Event::dispatch($event);
                    $row->forceFill([
                        'dispatched_at'     => now(),
                        'dispatch_attempts' => $row->dispatch_attempts + 1,
                        'last_error'        => null,
                    ])->save();
                    $count++;
                });
            } catch (\Throwable $e) {
                Log::error('Outbox dispatch failed', [
                    'event_id'   => $row->id,
                    'event_name' => $row->event_name,
                    'error'      => $e->getMessage(),
                ]);
                $row->forceFill([
                    'dispatch_attempts' => $row->dispatch_attempts + 1,
                    'last_error'        => $e->getMessage(),
                ])->save();
            }
        }

        return $count;
    }

    /**
     * Re-dispara eventos ja persistidos para um listener especifico (replay).
     * Util para reconstruir read-models/projecoes sem mexer no estado dos agregados.
     *
     * @param  callable(DomainEvent): void  $handler
     */
    public function replay(callable $handler, ?string $aggregateId = null, ?string $eventName = null): int
    {
        $query = OutboxEvent::query()->orderBy('occurred_at');
        if ($aggregateId) $query->where('aggregate_id', $aggregateId);
        if ($eventName) $query->where('event_name', $eventName);

        $count = 0;
        $query->lazyById(500)->each(function (OutboxEvent $row) use ($handler, &$count): void {
            $handler($this->reidratar($row));
            $count++;
        });

        return $count;
    }

    private function reidratar(OutboxEvent $row): DomainEvent
    {
        $key = "{$row->event_name}@v{$row->event_version}";
        $class = $this->eventMap[$key] ?? null;
        if (! $class || ! is_subclass_of($class, DomainEvent::class)) {
            throw new \RuntimeException("Domain Event nao registrado: {$key}");
        }

        return $class::fromOutbox([
            'id'             => $row->id,
            'aggregate_type' => $row->aggregate_type,
            'aggregate_id'   => $row->aggregate_id,
            'occurred_at'    => $row->occurred_at?->toIso8601String() ?? now()->toIso8601String(),
            'metadata'       => $row->metadata,
        ]);
    }
}
