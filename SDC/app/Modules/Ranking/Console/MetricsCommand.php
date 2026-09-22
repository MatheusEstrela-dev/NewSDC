<?php

declare(strict_types=1);

namespace App\Modules\Ranking\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

final class MetricsCommand extends Command
{
    protected $signature = 'ranking:metrics';

    protected $description = 'Emite metricas JSON da base propria e da fila do ranking';

    public function handle(): int
    {
        $metricas = [
            'habilitado' => (bool) config('ranking.habilitado'),
            'modo_sombra' => (bool) config('ranking.modo_sombra'),
            'database_disponivel' => false,
            'fila_disponivel' => false,
        ];

        try {
            $db = DB::connection('ranking_read');
            $metricas['transacoes_por_decisao'] = $db->table('ranking.transacoes')
                ->selectRaw('decisao, COUNT(*) AS total')->groupBy('decisao')->pluck('total', 'decisao')->all();
            $metricas['ultimo_registro_em'] = $db->table('ranking.transacoes')->max('criado_em');
            $metricas['geracao'] = (int) ($db->table('ranking.saldos')->max('geracao') ?? 1);
            $metricas['database_disponivel'] = true;
        } catch (\Throwable $e) {
            report($e);
        }

        try {
            $metricas['fila_pendente'] = Queue::connection('redis-ranking')->size(config('ranking.fila.nome', 'ranking'));
            $metricas['fila_disponivel'] = true;
        } catch (\Throwable $e) {
            report($e);
        }

        $this->line(json_encode($metricas, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));

        return $metricas['database_disponivel'] && $metricas['fila_disponivel'] ? self::SUCCESS : self::FAILURE;
    }
}
