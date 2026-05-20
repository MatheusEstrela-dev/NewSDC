<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\WebhookEvent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Move WebhookEvent com status=completed e idade > N dias para
 * webhook_events_archive. Mantem tabela operacional pequena,
 * preservando historico para auditoria.
 *
 * Uso: php artisan webhooks:archive --days=90 --chunk=500
 */
class ArchiveWebhookEventsCommand extends Command
{
    protected $signature = 'webhooks:archive
                            {--days=90 : idade minima em dias}
                            {--chunk=500 : tamanho do batch}';

    protected $description = 'Move WebhookEvent antigos com status completed para webhook_events_archive';

    public function handle(): int
    {
        $cutoff = now()->subDays((int) $this->option('days'));
        $chunkSize = (int) $this->option('chunk');
        $moved = 0;

        $this->info("Arquivando eventos completed criados antes de {$cutoff->toDateTimeString()}");

        WebhookEvent::query()
            ->where('status', WebhookEvent::STATUS_COMPLETED)
            ->where('created_at', '<', $cutoff)
            ->orderBy('id')
            ->chunkById($chunkSize, function ($batch) use (&$moved): void {
                $rows = $batch->map(fn ($e) => [
                    'id' => $e->id,
                    'external_event_id' => $e->external_event_id,
                    'provider' => $e->provider,
                    'event_type' => $e->event_type,
                    'payload' => is_array($e->payload) ? json_encode($e->payload) : $e->payload,
                    'signature' => $e->signature,
                    'status' => $e->status,
                    'attempts' => $e->attempts,
                    'processed_at' => $e->processed_at,
                    'last_attempt_at' => $e->last_attempt_at,
                    'error_message' => $e->error_message,
                    'created_at' => $e->created_at,
                    'updated_at' => $e->updated_at,
                    'archived_at' => now(),
                ])->all();

                DB::transaction(function () use ($batch, $rows, &$moved): void {
                    DB::table('webhook_events_archive')->insert($rows);
                    WebhookEvent::whereIn('id', $batch->pluck('id'))->delete();
                    $moved += count($rows);
                });
            });

        $this->info("Arquivados: {$moved}");

        return self::SUCCESS;
    }
}
