<?php

declare(strict_types=1);

namespace App\Core\Events;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;

/**
 * Listener base para garantir exactly-once.
 *
 * Antes de executar, registra (event_id, listener_class) em processed_events.
 * Se ja existir (UNIQUE constraint), pula. Isso garante idempotencia mesmo
 * com retries de fila.
 *
 * Subclasses implementam execute() e SAO LIVRES de implementar logica de
 * negocio sem se preocupar com duplicacao.
 */
abstract class IdempotentListener implements ShouldQueue
{
    abstract protected function execute(DomainEvent $event): void;

    public function handle(DomainEvent $event): void
    {
        DB::transaction(function () use ($event): void {
            $inserted = DB::table('processed_events')->insertOrIgnore([
                'event_id'        => $event->eventId,
                'listener_class'  => static::class,
                'processed_at'    => now(),
            ]);

            if ($inserted === 0) {
                // Ja processado por este listener; pula sem erro.
                return;
            }

            $this->execute($event);
        });
    }
}
