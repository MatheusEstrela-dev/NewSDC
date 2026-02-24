<?php

namespace App\Services\Webhook;

use App\Enums\RequestPriority;
use App\Exceptions\CircuitBreakerOpenException;
use App\Jobs\ProcessWebhook;
use App\Jobs\ProcessInboundWebhook;
use App\Models\WebhookLog;
use App\Models\WebhookEvent;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Servico de Webhooks
 * Gerencia envio e recebimento de webhooks com alta performance
 * Integrado com Circuit Breaker, Signature Validator e Idempotencia
 */
class WebhookService
{
    public function __construct(
        protected CircuitBreakerService $circuitBreaker,
        protected WebhookSignatureValidator $signatureValidator
    ) {}

    /**
     * Envia um webhook para uma URL especifica (outbound)
     * Usa filas para nao bloquear a requisicao principal
     * Verifica Circuit Breaker antes de enviar
     *
     * @throws CircuitBreakerOpenException
     */
    public function send(
        string $url,
        array $payload,
        array $headers = [],
        RequestPriority $priority = RequestPriority::NORMAL,
        ?int $userId = null
    ): void {
        $serviceKey = $this->getServiceKeyFromUrl($url);

        // Verifica Circuit Breaker
        if ($this->circuitBreaker->isOpen($serviceKey)) {
            Log::channel('webhooks')->warning('Webhook blocked by circuit breaker', [
                'url' => $url,
                'service' => $serviceKey,
            ]);
            throw new CircuitBreakerOpenException($serviceKey);
        }

        // Adiciona assinatura ao header
        $signature = $this->signatureValidator->generateSignature(json_encode($payload));
        $headers['X-Webhook-Signature'] = $signature;

        ProcessWebhook::dispatch($url, $payload, $headers, $userId)
            ->onQueue($priority->queue())
            ->delay(now()->addSeconds($priority->backoff()));
    }

    /**
     * Envia webhook de forma sincrona (apenas para testes ou casos criticos)
     * Integrado com Circuit Breaker
     */
    public function sendSync(
        string $url,
        array $payload,
        array $headers = [],
        int $timeout = 30
    ): array {
        $serviceKey = $this->getServiceKeyFromUrl($url);
        $startTime = microtime(true);

        // Verifica Circuit Breaker
        if ($this->circuitBreaker->isOpen($serviceKey)) {
            return [
                'success' => false,
                'error' => 'Circuit breaker is open for service: ' . $serviceKey,
                'circuit_breaker_status' => $this->circuitBreaker->getStatus($serviceKey),
            ];
        }

        try {
            // Adiciona assinatura
            $signature = $this->signatureValidator->generateSignature(json_encode($payload));
            $headers['X-Webhook-Signature'] = $signature;

            $response = Http::timeout($timeout)
                ->withHeaders(array_merge([
                    'Content-Type' => 'application/json',
                    'X-Webhook-Source' => config('app.name'),
                    'X-Webhook-Timestamp' => now()->toIso8601String(),
                ], $headers))
                ->post($url, $payload);

            $duration = round((microtime(true) - $startTime) * 1000, 2);

            $this->logOutboundWebhook($url, $payload, $response->status(), $response->body(), $duration);

            // Registra sucesso no circuit breaker
            $this->circuitBreaker->recordSuccess($serviceKey);

            return [
                'success' => $response->successful(),
                'status' => $response->status(),
                'body' => $response->json(),
                'duration_ms' => $duration,
            ];
        } catch (\Exception $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);

            $this->logOutboundWebhook($url, $payload, 0, $e->getMessage(), $duration, false);

            // Registra falha no circuit breaker
            $this->circuitBreaker->recordFailure($serviceKey);

            Log::channel('webhooks')->error('Webhook sync failed', [
                'url' => $url,
                'error' => $e->getMessage(),
                'service' => $serviceKey,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'duration_ms' => $duration,
            ];
        }
    }

    /**
     * Recebe webhook de sistema externo (inbound)
     * Controller "burro" - apenas valida e enfileira
     * Retorna imediatamente para responder 202 Accepted
     *
     * @param array $payload Dados validados do webhook
     * @param string $source Origem do webhook (header X-Webhook-Source)
     * @param string $traceId UUID para rastreamento
     * @return array Status do enfileiramento
     */
    public function receive(array $payload, string $source, string $traceId): array
    {
        // Gera external_event_id unico se nao fornecido
        $externalEventId = $payload['event_id'] ?? $payload['id'] ?? $traceId;
        $eventType = $payload['type'] ?? 'unknown';

        // Verifica idempotencia - evita processamento duplicado
        $existingEvent = WebhookEvent::where('external_event_id', $externalEventId)
            ->where('provider', $source)
            ->first();

        if ($existingEvent && $existingEvent->isProcessedOrProcessing()) {
            Log::channel('webhooks')->info('Webhook already processed (idempotency)', [
                'external_event_id' => $externalEventId,
                'provider' => $source,
                'status' => $existingEvent->status,
            ]);

            return [
                'status' => 'already_processed',
                'event_id' => $existingEvent->id,
                'original_status' => $existingEvent->status,
            ];
        }

        // Cria ou atualiza registro de evento
        $event = WebhookEvent::updateOrCreate(
            [
                'external_event_id' => $externalEventId,
                'provider' => $source,
            ],
            [
                'event_type' => $eventType,
                'payload' => $payload,
                'status' => WebhookEvent::STATUS_PENDING,
            ]
        );

        // Despacha job para processamento assincrono
        ProcessInboundWebhook::dispatch($event)
            ->onQueue(config('webhooks.queue.inbound', 'webhooks'));

        Log::channel('webhooks')->info('Webhook received and queued', [
            'event_id' => $event->id,
            'external_event_id' => $externalEventId,
            'provider' => $source,
            'type' => $eventType,
            'trace_id' => $traceId,
        ]);

        return [
            'status' => 'queued',
            'event_id' => $event->id,
            'trace_id' => $traceId,
        ];
    }

    /**
     * Valida assinatura de webhook recebido
     * Usado pelo controller antes de chamar receive()
     */
    public function validateSignature(string $rawPayload, string $signature, string $provider = 'default'): bool
    {
        return $this->signatureValidator->validate($rawPayload, $signature, $provider);
    }

    /**
     * Extrai chave do servico a partir da URL para o circuit breaker
     */
    protected function getServiceKeyFromUrl(string $url): string
    {
        $parsed = parse_url($url);
        return $parsed['host'] ?? 'unknown';
    }

    /**
     * Loga webhook de saida para auditoria
     */
    private function logOutboundWebhook(
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
                'payload' => config('webhooks.logging.log_payloads', false) ? $payload : [],
                'status_code' => $statusCode,
                'response' => $response,
                'duration_ms' => $duration,
                'success' => $success,
                'direction' => 'outbound',
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::channel('webhooks')->error('Failed to log outbound webhook', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
