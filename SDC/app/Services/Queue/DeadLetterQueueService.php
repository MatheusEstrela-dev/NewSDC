<?php

namespace App\Services\Queue;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;

/**
 * Serviço de Dead Letter Queue (DLQ)
 *
 * Baseado no papiro2.md - "Motor Assíncrono"
 *
 * Problema: Job falha 3 vezes e trava a fila inteira
 * Solução: Move para "fila de mortos" para análise posterior
 *
 * Jobs que falharam 3x vão para a tabela `failed_jobs` automaticamente.
 * Este serviço ajuda a gerenciar e reprocessar esses jobs.
 */
class DeadLetterQueueService
{
    /**
     * Lista todos os jobs que falharam
     */
    public static function listFailedJobs(int $limit = 50): array
    {
        return DB::table('failed_jobs')
            ->orderBy('failed_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($job) {
                return [
                    'id' => $job->id,
                    'uuid' => $job->uuid,
                    'connection' => $job->connection,
                    'queue' => $job->queue,
                    'exception' => substr($job->exception, 0, 200) . '...', // Trunca para legibilidade
                    'failed_at' => $job->failed_at,
                ];
            })
            ->toArray();
    }

    /**
     * Reprocessa um job específico que falhou
     */
    public static function retry(string $uuid): bool
    {
        try {
            Artisan::call('queue:retry', ['id' => $uuid]);

            Log::info('Failed job retried', [
                'uuid' => $uuid,
                'retried_by' => auth()->id() ?? 'system',
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to retry job', [
                'uuid' => $uuid,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Reprocessa TODOS os jobs que falharam
     */
    public static function retryAll(): int
    {
        $count = DB::table('failed_jobs')->count();

        Artisan::call('queue:retry', ['id' => 'all']);

        Log::warning('All failed jobs retried', [
            'count' => $count,
            'retried_by' => auth()->id() ?? 'system',
        ]);

        return $count;
    }

    /**
     * Remove um job falho (descarta permanentemente)
     */
    public static function forget(string $uuid): bool
    {
        try {
            Artisan::call('queue:forget', ['id' => $uuid]);

            Log::info('Failed job forgotten', [
                'uuid' => $uuid,
                'forgotten_by' => auth()->id() ?? 'system',
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to forget job', [
                'uuid' => $uuid,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Limpa TODOS os jobs falhos (cuidado!)
     */
    public static function flush(): int
    {
        $count = DB::table('failed_jobs')->count();

        Artisan::call('queue:flush');

        Log::warning('All failed jobs flushed', [
            'count' => $count,
            'flushed_by' => auth()->id() ?? 'system',
        ]);

        return $count;
    }

    /**
     * Estatísticas da Dead Letter Queue
     */
    public static function stats(): array
    {
        $failedJobs = DB::table('failed_jobs');

        return [
            'total' => $failedJobs->count(),
            'last_24h' => $failedJobs->where('failed_at', '>=', now()->subDay())->count(),
            'last_7d' => $failedJobs->where('failed_at', '>=', now()->subWeek())->count(),
            'by_queue' => $failedJobs
                ->select('queue', DB::raw('count(*) as count'))
                ->groupBy('queue')
                ->get()
                ->pluck('count', 'queue')
                ->toArray(),
            'by_connection' => $failedJobs
                ->select('connection', DB::raw('count(*) as count'))
                ->groupBy('connection')
                ->get()
                ->pluck('count', 'connection')
                ->toArray(),
        ];
    }

    /**
     * Analisa os erros mais comuns
     */
    public static function topErrors(int $limit = 10): array
    {
        $jobs = DB::table('failed_jobs')
            ->orderBy('failed_at', 'desc')
            ->limit(100) // Analisa os últimos 100 jobs
            ->get();

        $errors = [];

        foreach ($jobs as $job) {
            // Extrai a primeira linha da exceção (mensagem de erro)
            $exceptionLines = explode("\n", $job->exception);
            $errorMessage = $exceptionLines[0] ?? 'Unknown error';

            // Normaliza o erro (remove números, paths específicos)
            $normalizedError = preg_replace('/\d+/', 'N', $errorMessage);
            $normalizedError = preg_replace('/\/[^\s]+/', '/path', $normalizedError);

            if (!isset($errors[$normalizedError])) {
                $errors[$normalizedError] = [
                    'error' => $errorMessage,
                    'count' => 0,
                    'last_occurrence' => $job->failed_at,
                ];
            }

            $errors[$normalizedError]['count']++;
        }

        // Ordena por frequência
        uasort($errors, fn($a, $b) => $b['count'] <=> $a['count']);

        return array_slice($errors, 0, $limit);
    }

    /**
     * Verifica se a fila está "doente" (muitos jobs falhando)
     */
    public static function isUnhealthy(): bool
    {
        $recentFailed = DB::table('failed_jobs')
            ->where('failed_at', '>=', now()->subHour())
            ->count();

        // Se mais de 10 jobs falharam na última hora, a fila está doente
        return $recentFailed > 10;
    }

    /**
     * Alerta sobre fila doente (para uso em healthcheck)
     */
    public static function healthCheck(): array
    {
        $stats = self::stats();
        $isUnhealthy = self::isUnhealthy();

        return [
            'status' => $isUnhealthy ? 'unhealthy' : 'healthy',
            'total_failed' => $stats['total'],
            'failed_last_hour' => $stats['last_24h'],
            'threshold' => 10,
            'message' => $isUnhealthy
                ? "Queue unhealthy: {$stats['last_24h']} jobs failed in last hour"
                : 'Queue is healthy',
        ];
    }
}
