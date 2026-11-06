<?php

declare(strict_types=1);

namespace App\Core\Events;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;

/**
 * Deduplicacao dos efeitos transacionais de um consumidor.
 *
 * Antes de executar, registra (event_id, listener_class) em processed_events.
 * Se ja existir (UNIQUE constraint), pula. O marcador e o efeito devem usar
 * a mesma conexao e transacao. Chamadas externas e outras conexoes exigem
 * idempotencia propria; este registro nao torna esses efeitos atomicos.
 *
 * Subclasses que gravam em banco dedicado selecionam databaseConnection()
 * e usam essa mesma conexao em execute().
 */
abstract class IdempotentListener implements ShouldQueue
{
    abstract protected function execute(DomainEvent $event): void;

    protected function databaseConnection(): ?string
    {
        return null;
    }

    public function handle(DomainEvent $event): void
    {
        $db = DB::connection($this->databaseConnection());
        $db->transaction(function () use ($event, $db): void {
            $inserted = $db->table('processed_events')->insertOrIgnore([
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
