<?php

namespace App\Services\Webhook;

use App\Enums\RequestPriority;
use App\Jobs\ProcessWebhook;
use App\Models\WebhookLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Serviço de Webhooks
 * Gerencia envio e recebimento de webhooks com alta performance
 */
class WebhookService
{
    /**
     * Envia um webhook para uma URL específica
     * Usa filas para não bloquear a requisição principal
     */
    public function send(
        string $url,
        array $payload,
        array $headers = [],
        RequestPriority $priority = RequestPriority::NORMAL,
        ?int $userId = null
    ): void {
        ProcessWebhook::dispatch($url, $payload, $headers, $userId)
            ->onQueue($priority->queue())
            ->delay(now()->addSeconds($priority->backoff()));
    }

    /**
     * Envia webhook de forma síncrona (apenas para testes ou casos críticos)
     */
    public function sendSync(
        string $url,
        array $payload,
        array $headers = [],
        int $timeout = 30
    ): array {
        try {
            $startTime = microtime(true);

            $response = Http::timeout($timeout)
                ->withHeaders(array_merge([
                    'Content-Type' => 'application/json',
                    'X-Webhook-Source' => config('app.name'),
                    'X-Webhook-Timestamp' => now()->toIso8601String(),
                ], $headers))
                ->post($url, $payload);

            $duration = round((microtime(true) - $startTime) * 1000, 2);

            $this->logWebhook($url, $payload, $response->status(), $response->body(), $duration);

            return [
                'success' => $response->successful(),
                'status' => $response->status(),
                'body' => $response->json(),
                'duration_ms' => $duration,
            ];
        } catch (\Exception $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);

            $this->logWebhook($url, $payload, 0, $e->getMessage(), $duration, false);

            Log::error('Webhook failed', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'duration_ms' => $duration,
            ];
        }
    }

    /**
     * Processa webhook recebido
     */
    public function receive(array $payload, string $source): array
    {
        // Valida assinatura/autenticação do webhook
        if (!$this->validateWebhookSignature($payload, $source)) {
            throw new \Exception('Invalid webhook signature');
        }

        // Processa o webhook conforme o tipo
        return $this->processWebhookByType($payload);
    }

    /**
     * Valida assinatura do webhook para segurança
     */
    private function validateWebhookSignature(array $payload, string $source): bool
    {
        // Implementar validação de assinatura HMAC
        // Exemplo: verificar header X-Webhook-Signature
        return true; // Placeholder
    }

    /**
     * Processa webhook baseado no tipo
     */
    private function processWebhookByType(array $payload): array
    {
        $type = $payload['type'] ?? 'unknown';

        return match($type) {
            'payment.completed' => $this->handlePaymentCompleted($payload),
            'user.created' => $this->handleUserCreated($payload),
            'data.sync' => $this->handleDataSync($payload),
            default => $this->handleGenericWebhook($payload),
        };
    }

    /**
     * Loga webhook para auditoria e debugging
     */
    private function logWebhook(
        string $url,
        array $payload,
        int $statusCode,
        string $response,
        float $duration,
        bool $success = true
    ): void {
        try {
            WebhookLog::create([
                'url' => $url,
                'payload' => $payload,
                'status_code' => $statusCode,
                'response' => $response,
                'duration_ms' => $duration,
                'success' => $success,
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log webhook', ['error' => $e->getMessage()]);
        }
    }

    // Handlers específicos por tipo de webhook
    private function handlePaymentCompleted(array $payload): array
    {
        // Implementar lógica de pagamento completado
        return ['status' => 'processed', 'type' => 'payment.completed'];
    }

    private function handleUserCreated(array $payload): array
    {
        // Implementar lógica de usuário criado
        return ['status' => 'processed', 'type' => 'user.created'];
    }

    private function handleDataSync(array $payload): array
    {
        // Implementar lógica de sincronização
        return ['status' => 'processed', 'type' => 'data.sync'];
    }

    private function handleGenericWebhook(array $payload): array
    {
        // Handler genérico
        return ['status' => 'processed', 'type' => 'generic'];
    }
}
