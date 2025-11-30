<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\WebhookLog;

/**
 * Job para processar webhooks de forma assíncrona
 * Otimizado para alta carga (100k+ requisições)
 */
class ProcessWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Número de tentativas
     */
    public int $tries = 3;

    /**
     * Timeout em segundos
     */
    public int $timeout = 45;

    /**
     * Backoff entre tentativas (em segundos)
     */
    public array $backoff = [10, 30, 60];

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $url,
        public array $payload,
        public array $headers = [],
        public ?int $userId = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $startTime = microtime(true);

        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders(array_merge([
                    'Content-Type' => 'application/json',
                    'X-Webhook-Source' => config('app.name'),
                    'X-Webhook-Timestamp' => now()->toIso8601String(),
                    'X-Webhook-Attempt' => $this->attempts(),
                ], $this->headers))
                ->post($this->url, $this->payload);

            $duration = round((microtime(true) - $startTime) * 1000, 2);

            // Log sucesso
            $this->logWebhook($response->status(), $response->body(), $duration, true);

            if (!$response->successful()) {
                throw new \Exception("Webhook failed with status {$response->status()}");
            }

        } catch (\Exception $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);

            // Log falha
            $this->logWebhook(0, $e->getMessage(), $duration, false);

            Log::error('Webhook processing failed', [
                'url' => $this->url,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
                'user_id' => $this->userId,
            ]);

            // Re-lança exceção para retry automático
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::critical('Webhook permanently failed after all retries', [
            'url' => $this->url,
            'error' => $exception->getMessage(),
            'user_id' => $this->userId,
            'attempts' => $this->tries,
        ]);

        // Notificar administradores ou criar alerta
    }

    /**
     * Determina o tempo de delay antes do próximo retry
     */
    public function retryUntil(): \DateTime
    {
        return now()->addMinutes(10);
    }

    /**
     * Loga o webhook para auditoria
     */
    private function logWebhook(int $statusCode, string $response, float $duration, bool $success): void
    {
        try {
            WebhookLog::create([
                'url' => $this->url,
                'payload' => $this->payload,
                'status_code' => $statusCode,
                'response' => $response,
                'duration_ms' => $duration,
                'success' => $success,
                'user_id' => $this->userId,
                'attempt' => $this->attempts(),
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log webhook', ['error' => $e->getMessage()]);
        }
    }
}
