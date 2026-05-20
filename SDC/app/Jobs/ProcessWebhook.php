<?php

namespace App\Jobs;

use App\Services\Webhook\CircuitBreakerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\WebhookLog;
use Throwable;

/**
 * Job para processar webhooks de saida (outbound) de forma assincrona
 * Integrado com Circuit Breaker e Dead Letter Queue
 * Otimizado para alta carga (100k+ requisicoes)
 */
class ProcessWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Numero de tentativas - 4 para permitir backoff exponencial completo
     */
    public int $tries = 4;

    /**
     * Timeout em segundos
     */
    public int $timeout = 45;

    /**
     * Backoff exponencial entre tentativas (em segundos)
     * 5s -> 30s -> 5min -> 1h (conforme tasklimit.md)
     */
    public array $backoff = [5, 30, 300, 3600];

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
    public function handle(CircuitBreakerService $circuitBreaker): void
    {
        $defaultConnection = config('database.default');
        DB::setDefaultConnection('pgsql_webhook');

        try {
            $serviceKey = $this->getServiceKey();
            $startTime = microtime(true);

            // Verifica Circuit Breaker antes de tentar
            if ($circuitBreaker->isOpen($serviceKey)) {
            Log::channel('webhooks')->warning('Webhook job blocked by circuit breaker', [
                'url' => $this->url,
                'service' => $serviceKey,
                'attempt' => $this->attempts(),
            ]);

            // Relanca para tentar novamente quando circuit fechar
            throw new \Exception("Circuit breaker is open for service: {$serviceKey}");
        }

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

            if ($response->successful()) {
                // Registra sucesso no circuit breaker
                $circuitBreaker->recordSuccess($serviceKey);

                Log::channel('webhooks')->info('Webhook sent successfully', [
                    'url' => $this->url,
                    'status' => $response->status(),
                    'duration_ms' => $duration,
                ]);
            } else {
                // Status HTTP de erro (4xx, 5xx)
                $circuitBreaker->recordFailure($serviceKey);
                throw new \Exception("Webhook failed with status {$response->status()}");
            }

        } catch (Throwable $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);

            // Log falha
            $this->logWebhook(0, $e->getMessage(), $duration, false);

            // Registra falha no circuit breaker
            $circuitBreaker->recordFailure($serviceKey);

            Log::channel('webhooks')->error('Webhook processing failed', [
                'url' => $this->url,
                'service' => $serviceKey,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
                'user_id' => $this->userId,
            ]);

            // Re-lanca excecao para retry automatico
            throw $e;
        }
        } finally {
            DB::setDefaultConnection($defaultConnection);
        }
    }

    /**
     * Handle a job failure - move para Dead Letter Queue
     */
    public function failed(Throwable $exception): void
    {
        $serviceKey = $this->getServiceKey();

        Log::channel('webhooks')->critical('Webhook permanently failed - moving to DLQ', [
            'url' => $this->url,
            'service' => $serviceKey,
            'error' => $exception->getMessage(),
            'user_id' => $this->userId,
            'attempts' => $this->tries,
        ]);

        // Cria registro na dead letter queue para analise manual
        try {
            WebhookLog::create([
                'url' => $this->url,
                'payload' => $this->payload,
                'status_code' => 0,
                'response' => 'DEAD_LETTER: ' . $exception->getMessage(),
                'duration_ms' => 0,
                'success' => false,
                'user_id' => $this->userId,
                'attempt' => $this->tries,
                'direction' => 'outbound',
                'is_dead_letter' => true,
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::channel('webhooks')->error('Failed to create DLQ record', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Determina o tempo maximo de retry
     */
    public function retryUntil(): \DateTime
    {
        // Permite retry por ate 2 horas (para cobrir todos os backoffs)
        return now()->addHours(2);
    }

    /**
     * Extrai chave do servico da URL para circuit breaker
     */
    private function getServiceKey(): string
    {
        $parsed = parse_url($this->url);
        return $parsed['host'] ?? 'unknown';
    }

    /**
     * Loga o webhook para auditoria
     */
    private function logWebhook(int $statusCode, string $response, float $duration, bool $success): void
    {
        try {
            WebhookLog::create([
                'url' => $this->url,
                'payload' => config('webhooks.logging.log_payloads', false) ? $this->payload : [],
                'status_code' => $statusCode,
                'response' => $response,
                'duration_ms' => $duration,
                'success' => $success,
                'user_id' => $this->userId,
                'attempt' => $this->attempts(),
                'direction' => 'outbound',
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::channel('webhooks')->error('Failed to log webhook', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
