<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Core\Events\DomainEvent;
use App\Core\Outbox\OutboxDispatcher;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Event;

/**
 * Re-dispara eventos do outbox para reconstruir projecoes/listeners.
 *
 * Uso:
 *   php artisan event:replay --aggregate-id=<uuid>
 *   php artisan event:replay --event-name=tdap.viagem.validada
 *   php artisan event:replay --listener=AtualizarProjecaoProcessoListener
 *   php artisan event:replay --dry-run
 *
 * NAO altera estado dos agregados — apenas redispara para listeners.
 * Idempotencia (processed_events) garante zero efeito duplicado se o
 * listener ja processou o evento antes.
 */
class EventReplay extends Command
{
    protected $signature = 'event:replay
        {--aggregate-id= : Filtra por aggregate_id (UUID)}
        {--event-name= : Filtra por event_name (ex: tdap.viagem.validada)}
        {--listener= : Nome da classe do listener (sem namespace)}
        {--dry-run : So conta, nao dispara}';

    protected $description = 'Re-dispara Domain Events do outbox para reconstruir projecoes.';

    public function handle(OutboxDispatcher $dispatcher): int
    {
        $aggregateId = $this->option('aggregate-id') ?: null;
        $eventName   = $this->option('event-name') ?: null;
        $dryRun      = (bool) $this->option('dry-run');
        $listener    = $this->option('listener') ?: null;

        $count = $dispatcher->replay(
            function (DomainEvent $event) use ($dryRun, $listener): void {
                if ($dryRun) return;
                if ($listener) {
                    // Resolve classe pelo basename
                    $candidates = array_filter(
                        Event::getRawListeners($event::class) ?: [],
                        fn ($l) => is_string($l) && str_ends_with($l, '\\'.$listener)
                    );
                    foreach ($candidates as $listenerClass) {
                        app($listenerClass)->handle($event);
                    }

                    return;
                }
                Event::dispatch($event);
            },
            $aggregateId,
            $eventName,
        );

        $this->info(($dryRun ? '[DRY-RUN] ' : '')."Total de eventos: {$count}");

        return self::SUCCESS;
    }
}
