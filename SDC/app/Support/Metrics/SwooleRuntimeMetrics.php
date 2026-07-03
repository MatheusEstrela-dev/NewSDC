<?php

declare(strict_types=1);

namespace App\Support\Metrics;

/**
 * Converte as stats do server Swoole em linhas Prometheus (text v0.0.4).
 * Compartilhado pelo /api/metrics (scrape) e /api/health/metrics (admin).
 * Fora do server Swoole (RoadRunner, CLI, task/queue) retorna lista vazia.
 */
final class SwooleRuntimeMetrics
{
    /**
     * @return list<string>
     */
    public static function linhas(): array
    {
        if (! app()->bound(\Swoole\Http\Server::class)) {
            return [];
        }

        try {
            $stats = app(\Swoole\Http\Server::class)->stats();
        } catch (\Throwable) {
            return [];
        }

        $linhas = [];

        $gauges = [
            'sdc_swoole_connections' => ['connection_num', 'Conexoes TCP abertas no server'],
            'sdc_swoole_workers_total' => ['worker_num', 'Workers HTTP configurados'],
            'sdc_swoole_workers_idle' => ['idle_worker_num', 'Workers HTTP ociosos'],
            'sdc_swoole_task_workers_total' => ['task_worker_num', 'Task workers configurados'],
            'sdc_swoole_task_workers_idle' => ['task_idle_worker_num', 'Task workers ociosos'],
            'sdc_swoole_tasking_num' => ['tasking_num', 'Tasks aguardando/executando'],
            'sdc_swoole_coroutines' => ['coroutine_num', 'Coroutines ativas'],
        ];

        foreach ($gauges as $nome => [$chave, $help]) {
            if (isset($stats[$chave])) {
                $linhas[] = "# HELP {$nome} {$help}";
                $linhas[] = "# TYPE {$nome} gauge";
                $linhas[] = "{$nome} " . (int) $stats[$chave];
            }
        }

        if (isset($stats['request_count'])) {
            $linhas[] = '# HELP sdc_swoole_requests_total Requests atendidas desde o boot do server';
            $linhas[] = '# TYPE sdc_swoole_requests_total counter';
            $linhas[] = 'sdc_swoole_requests_total ' . (int) $stats['request_count'];
        }

        if (isset($stats['start_time'])) {
            $linhas[] = '# HELP sdc_swoole_uptime_seconds Uptime do server Swoole';
            $linhas[] = '# TYPE sdc_swoole_uptime_seconds gauge';
            $linhas[] = 'sdc_swoole_uptime_seconds ' . (time() - (int) $stats['start_time']);
        }

        return $linhas;
    }
}
