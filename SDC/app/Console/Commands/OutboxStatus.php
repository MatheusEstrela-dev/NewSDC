<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Core\Outbox\OutboxEvent;
use App\Core\Outbox\OutboxRetryPolicy;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

final class OutboxStatus extends Command
{
    protected $signature = 'outbox:status {--limit=20 : Maximo de eventos suspensos exibidos}';

    protected $description = 'Consulta pendencias e eventos com tentativas esgotadas, sem publicar.';

    public function handle(OutboxRetryPolicy $policy): int
    {
        $model = new OutboxEvent;
        $query = $model->getConnection()->table($model->getTable())->useWritePdo();
        $suspensos = $policy->esgotados(clone $query);

        $this->table(['Estado', 'Quantidade'], [
            ['Pendentes (inclui espera e suspensos)', (clone $query)->whereNull('dispatched_at')->count()],
            ['Elegiveis agora', $policy->elegiveis(clone $query)->count()],
            ['Suspensos por limite', (clone $suspensos)->count()],
        ]);
        $this->line('Limite por ciclo: '.$policy->limite().'. Contagens momentaneas; consumidores podem estar ativos.');

        $rows = $suspensos->orderBy('occurred_at')->orderBy('id')
            ->limit(max(1, min(100, (int) $this->option('limit'))))
            ->get(['id', 'event_name', 'dispatch_attempts', 'updated_at', 'last_error']);

        if ($rows->isNotEmpty()) {
            $this->table(['ID', 'Evento', 'Tentativas', 'Ultima tentativa', 'Ultimo erro'], $rows->map(
                static fn (object $row): array => [
                    $row->id, $row->event_name, $row->dispatch_attempts, $row->updated_at,
                    Str::limit((string) $row->last_error, 160),
                ]
            )->all());
        }

        return self::SUCCESS;
    }
}
