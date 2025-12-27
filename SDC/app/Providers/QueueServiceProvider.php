<?php

namespace App\Providers;

use App\Services\Logging\ActivityLogger;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

/**
 * Service Provider para monitoramento completo de Jobs e Queues
 *
 * Logs estruturados de:
 * - Início do processamento
 * - Conclusão (sucesso/falha)
 * - Tempo de execução
 * - Payload e contexto
 */
class QueueServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // ========================================
        // LOG: Job começou a processar
        // ========================================
        Queue::before(function (JobProcessing $event) {
            $jobId = $this->getJobId($event);

            $logData = [
                'job_id' => $jobId,
                'job_name' => $event->job->getName(),
                'queue' => $event->job->getQueue(),
                'connection' => $event->connectionName,
                'attempts' => $event->job->attempts(),
                'max_tries' => $event->job->maxTries(),
                'timeout' => $event->job->timeout(),
                'payload' => $this->getJobPayload($event),
                'started_at' => now()->toIso8601String(),
            ];

            Log::channel('jobs')->info('Job Processing Started', $logData);

            // Armazena o tempo de início no cache para calcular duração depois
            cache()->put("job:{$jobId}:start_time", microtime(true), 3600);
        });

        // ========================================
        // LOG: Job processado com SUCESSO
        // ========================================
        Queue::after(function (JobProcessed $event) {
            $jobId = $this->getJobId($event);
            $duration = $this->getJobDuration($jobId);

            $logData = [
                'job_id' => $jobId,
                'job_name' => $event->job->getName(),
                'queue' => $event->job->getQueue(),
                'connection' => $event->connectionName,
                'duration_ms' => $duration,
                'attempts' => $event->job->attempts(),
                'status' => 'success',
                'completed_at' => now()->toIso8601String(),
            ];

            Log::channel('jobs')->info('Job Processed Successfully', $logData);

            ActivityLogger::logPerformance(
                operation: 'job_processed',
                duration: $duration,
                metrics: [
                    'job_name' => $event->job->getName(),
                    'queue' => $event->job->getQueue(),
                    'attempts' => $event->job->attempts(),
                ]
            );

            // Limpa o cache
            cache()->forget("job:{$jobId}:start_time");

            // Log se foi lento
            if ($duration > 5000) { // > 5 segundos
                Log::channel('jobs')->warning('Slow Job Detected', array_merge($logData, [
                    'threshold_exceeded_by_ms' => round($duration - 5000, 2),
                ]));
            }
        });

        // ========================================
        // LOG: Job FALHOU
        // ========================================
        Queue::failing(function (JobFailed $event) {
            $jobId = $this->getJobId($event);
            $duration = $this->getJobDuration($jobId);

            $logData = [
                'job_id' => $jobId,
                'job_name' => $event->job->getName(),
                'queue' => $event->job->getQueue(),
                'connection' => $event->connectionName,
                'duration_ms' => $duration,
                'attempts' => $event->job->attempts(),
                'status' => 'failed',
                'failed_at' => now()->toIso8601String(),

                // Detalhes da exceção
                'exception' => [
                    'class' => get_class($event->exception),
                    'message' => $event->exception->getMessage(),
                    'file' => $event->exception->getFile(),
                    'line' => $event->exception->getLine(),
                    'trace' => collect($event->exception->getTrace())
                        ->take(5)
                        ->map(fn($t) => [
                            'file' => basename($t['file'] ?? 'unknown'),
                            'line' => $t['line'] ?? 0,
                            'function' => $t['function'] ?? 'unknown',
                        ])
                        ->toArray(),
                ],

                // Payload do job (para debug)
                'payload' => $this->getJobPayload($event),
            ];

            Log::channel('jobs')->error('Job Failed', $logData);

            // Log crítico se falhou após várias tentativas
            if ($event->job->attempts() >= ($event->job->maxTries() ?? 3)) {
                Log::channel('critical')->critical('Job Failed After Max Attempts', $logData);

                ActivityLogger::logCriticalError(
                    message: "Job failed after {$event->job->attempts()} attempts: {$event->job->getName()}",
                    exception: $event->exception,
                    context: [
                        'job_name' => $event->job->getName(),
                        'queue' => $event->job->getQueue(),
                        'attempts' => $event->job->attempts(),
                        'payload' => $this->getJobPayload($event),
                    ]
                );
            }

            // Limpa o cache
            cache()->forget("job:{$jobId}:start_time");
        });
    }

    /**
     * Obtém ID único do job
     */
    private function getJobId($event): string
    {
        return $event->job->getJobId() ?? (string) Str::uuid();
    }

    /**
     * Calcula duração do job em milissegundos
     */
    private function getJobDuration(string $jobId): float
    {
        $startTime = cache()->get("job:{$jobId}:start_time");

        if (!$startTime) {
            return 0.0;
        }

        return (microtime(true) - $startTime) * 1000;
    }

    /**
     * Extrai payload do job de forma segura
     */
    private function getJobPayload($event): array
    {
        try {
            $payload = $event->job->payload();

            // Remove dados muito grandes ou sensíveis
            return [
                'displayName' => $payload['displayName'] ?? 'unknown',
                'job_class' => $payload['data']['commandName'] ?? 'unknown',
                'data_size_bytes' => strlen(json_encode($payload)),
                // Não loga o conteúdo completo em produção
                'data_preview' => config('app.env') === 'production'
                    ? 'hidden in production'
                    : substr(json_encode($payload['data'] ?? []), 0, 500),
            ];
        } catch (\Exception $e) {
            return ['error' => 'Failed to extract payload'];
        }
    }
}
