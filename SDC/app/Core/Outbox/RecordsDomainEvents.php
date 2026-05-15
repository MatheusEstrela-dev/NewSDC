<?php

declare(strict_types=1);

namespace App\Core\Outbox;

use App\Core\Events\DomainEvent;

/**
 * Trait para agregados que emitem Domain Events.
 *
 * Uso no Service (dentro de DB::transaction):
 *
 *   $aggregate->recordEvent(new MeuEventoV1(...));
 *   $aggregate->save();
 *   foreach ($aggregate->pullPendingEvents() as $event) {
 *       OutboxEvent::create([...$event...]);
 *   }
 *
 * Pull garante que eventos sao processados uma unica vez (esvaziados apos
 * persistencia no outbox).
 */
trait RecordsDomainEvents
{
    /** @var DomainEvent[] */
    private array $pendingEvents = [];

    public function recordEvent(DomainEvent $event): void
    {
        $this->pendingEvents[] = $event;
    }

    /**
     * @return DomainEvent[]
     */
    public function pullPendingEvents(): array
    {
        $events = $this->pendingEvents;
        $this->pendingEvents = [];

        return $events;
    }
}
