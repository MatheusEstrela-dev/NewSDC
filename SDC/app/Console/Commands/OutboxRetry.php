<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Core\Outbox\OutboxEvent;
use App\Core\Outbox\OutboxRetryPolicy;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

final class OutboxRetry extends Command
{
    protected $signature = 'outbox:retry {id : UUID do evento suspenso} {--dry-run : Consulta sem alterar}';

    protected $description = 'Reabre um ciclo de tentativas de um evento suspenso, sem publica-lo imediatamente.';

    public function handle(OutboxRetryPolicy $policy): int
    {
        $id = (string) $this->argument('id');
        if (! Str::isUuid($id)) {
            $this->error('Informe um UUID de evento valido.');
            return self::INVALID;
        }

        $model = new OutboxEvent;
        $db = $model->getConnection();
        $dryRun = (bool) $this->option('dry-run');
        $row = $db->transaction(function () use ($db, $model, $id, $dryRun, $policy): ?object {
            $query = $policy->esgotados($db->table($model->getTable()))->where('id', $id)->useWritePdo();
            $row = ($dryRun ? $query : $query->lock('FOR UPDATE SKIP LOCKED'))->first();
            if ($row !== null && ! $dryRun) {
                // Reinicia o ciclo, mantendo ID, payload e ultimo erro para
                // diagnostico. O erro so e limpo quando a publicacao sucede.
                $db->table($model->getTable())->where('id', $id)->update([
                    'dispatch_attempts' => 0,
                    'updated_at' => now(),
                ]);
            }
            return $row;
        });

        if ($row === null) {
            $this->error('Evento nao encontrado, nao suspenso ou reservado por outro consumidor.');
            return self::FAILURE;
        }

        $this->info(($dryRun ? '[DRY-RUN] Elegivel para retomada: ' : 'Novo ciclo liberado: ').$id);
        $this->line('Tentativas no ciclo anterior: '.$row->dispatch_attempts.'. Nenhum evento foi publicado por este comando.');

        return self::SUCCESS;
    }
}
