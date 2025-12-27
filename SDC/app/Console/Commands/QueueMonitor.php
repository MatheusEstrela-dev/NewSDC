<?php

namespace App\Console\Commands;

use App\Services\Queue\DeadLetterQueueService;
use Illuminate\Console\Command;

/**
 * Comando para monitorar e gerenciar a Dead Letter Queue
 *
 * Uso:
 * php artisan queue:monitor           # Mostra estatísticas
 * php artisan queue:monitor --list    # Lista jobs falhos
 * php artisan queue:monitor --errors  # Top erros
 */
class QueueMonitor extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:monitor
                            {--list : Lista todos os jobs que falharam}
                            {--errors : Mostra os erros mais comuns}
                            {--health : Verifica saúde da fila}
                            {--limit=20 : Número de resultados}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitora a Dead Letter Queue e mostra estatísticas';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($this->option('list')) {
            $this->listFailedJobs();
            return 0;
        }

        if ($this->option('errors')) {
            $this->showTopErrors();
            return 0;
        }

        if ($this->option('health')) {
            $this->showHealth();
            return 0;
        }

        // Padrão: mostra estatísticas
        $this->showStats();
        return 0;
    }

    /**
     * Mostra estatísticas gerais
     */
    private function showStats(): void
    {
        $stats = DeadLetterQueueService::stats();

        $this->info('📊 Dead Letter Queue Statistics');
        $this->newLine();

        $this->line("Total failed jobs: <fg=red>{$stats['total']}</>");
        $this->line("Failed in last 24h: <fg=yellow>{$stats['last_24h']}</>");
        $this->line("Failed in last 7d: {$stats['last_7d']}");

        $this->newLine();
        $this->info('By Queue:');
        foreach ($stats['by_queue'] as $queue => $count) {
            $this->line("  - {$queue}: {$count}");
        }

        $this->newLine();
        $this->info('By Connection:');
        foreach ($stats['by_connection'] as $connection => $count) {
            $this->line("  - {$connection}: {$count}");
        }

        $this->newLine();
        $this->comment('💡 Use --list para ver detalhes dos jobs');
        $this->comment('💡 Use --errors para ver erros mais comuns');
        $this->comment('💡 Use --health para verificar saúde da fila');
    }

    /**
     * Lista jobs que falharam
     */
    private function listFailedJobs(): void
    {
        $limit = (int) $this->option('limit');
        $jobs = DeadLetterQueueService::listFailedJobs($limit);

        if (empty($jobs)) {
            $this->info('✅ Nenhum job falho encontrado!');
            return;
        }

        $this->info("📋 Failed Jobs (últimos {$limit})");
        $this->newLine();

        $headers = ['UUID', 'Queue', 'Connection', 'Failed At', 'Exception'];
        $rows = array_map(function ($job) {
            return [
                substr($job['uuid'], 0, 8) . '...',
                $job['queue'],
                $job['connection'],
                $job['failed_at'],
                substr($job['exception'], 0, 50) . '...',
            ];
        }, $jobs);

        $this->table($headers, $rows);

        $this->newLine();
        $this->comment('💡 Para reprocessar: php artisan queue:retry {uuid}');
        $this->comment('💡 Para reprocessar todos: php artisan queue:retry all');
    }

    /**
     * Mostra os erros mais comuns
     */
    private function showTopErrors(): void
    {
        $limit = (int) $this->option('limit');
        $errors = DeadLetterQueueService::topErrors($limit);

        if (empty($errors)) {
            $this->info('✅ Nenhum erro encontrado!');
            return;
        }

        $this->info("🔥 Top {$limit} Erros Mais Comuns");
        $this->newLine();

        $headers = ['#', 'Ocorrências', 'Erro', 'Última Vez'];
        $rows = [];
        $index = 1;

        foreach ($errors as $error) {
            $rows[] = [
                $index++,
                $error['count'],
                substr($error['error'], 0, 80),
                $error['last_occurrence'],
            ];
        }

        $this->table($headers, $rows);

        $this->newLine();
        $this->comment('💡 Analise os erros mais frequentes para identificar problemas sistêmicos');
    }

    /**
     * Verifica saúde da fila
     */
    private function showHealth(): void
    {
        $health = DeadLetterQueueService::healthCheck();

        $this->newLine();

        if ($health['status'] === 'healthy') {
            $this->info('✅ Queue is HEALTHY');
        } else {
            $this->error('❌ Queue is UNHEALTHY');
        }

        $this->newLine();
        $this->line("Total failed jobs: {$health['total_failed']}");
        $this->line("Failed last hour: {$health['failed_last_hour']}");
        $this->line("Threshold: {$health['threshold']}");
        $this->newLine();
        $this->line("Message: {$health['message']}");
        $this->newLine();

        if ($health['status'] === 'unhealthy') {
            $this->warn('⚠️  AÇÃO RECOMENDADA: Investigue os erros com --errors');
        }
    }
}
