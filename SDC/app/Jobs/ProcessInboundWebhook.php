<?php

namespace App\Jobs;

use App\Models\WebhookEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Job para processar webhooks recebidos (inbound)
 * Implementa exponential backoff e dead letter queue
 */
class ProcessInboundWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Numero de tentativas - 4 para permitir backoff exponencial completo
     */
    public int $tries = 4;

    /**
     * Timeout em segundos
     */
    public int $timeout = 60;

    /**
     * Backoff exponencial entre tentativas (em segundos)
     * 5s -> 30s -> 5min -> 1h
     */
    public array $backoff = [5, 30, 300, 3600];

    /**
     * Duas formas de despacho:
     *  - legado: dispatch($event) com o WebhookEvent ja persistido;
     *  - atual: dispatch(null, $inbound) com o payload cru — a idempotencia e
     *    o upsert em webhook_events rodam AQUI (worker de fila), tirando as
     *    queries do hot path HTTP do receive().
     *
     * @param array{payload: array, source: string, external_event_id: string, event_type: string, trace_id: string}|null $inbound
     */
    public function __construct(
        public ?WebhookEvent $event = null,
        public ?array $inbound = null
    ) {}

    /**
     * Processa o webhook. O isolamento da conexao pgsql_webhook eh feito
     * pelo proprio model WebhookEvent (protected $connection = 'pgsql_webhook'),
     * dispensando o padrao anterior de DB::setDefaultConnection que alterava
     * estado global e podia vazar em Octane.
     */
    public function handle(): void
    {
        if ($this->event === null) {
            $this->event = $this->resolveEvent();

            if ($this->event === null) {
                return; // Duplicata: evento ja concluido ou em processamento ativo.
            }
        }

        // Marca como em processamento
        $this->event->markAsProcessing();

        Log::channel('webhooks')->info('Processing inbound webhook', [
            'event_id' => $this->event->id,
            'external_event_id' => $this->event->external_event_id,
            'provider' => $this->event->provider,
            'type' => $this->event->event_type,
            'attempt' => $this->attempts(),
        ]);

        try {
            // Processa baseado no tipo de evento
            $result = $this->processWebhookByType(
                $this->event->payload,
                $this->event->event_type
            );

            // Marca como concluido
            $this->event->markAsCompleted();

            Log::channel('webhooks')->info('Inbound webhook processed successfully', [
                'event_id' => $this->event->id,
                'result' => $result,
            ]);

        } catch (Throwable $e) {
            Log::channel('webhooks')->error('Inbound webhook processing failed', [
                'event_id' => $this->event->id,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Resolve (idempotente) o WebhookEvent a partir do payload cru despachado
     * pelo receive(). Retorna null quando a entrega e duplicata: evento ja
     * COMPLETED, ou PROCESSING com tentativa recente (outro worker ativo).
     * PROCESSING antigo (> timeout) e tentativa que crashou — retoma.
     */
    protected function resolveEvent(): ?WebhookEvent
    {
        $existing = WebhookEvent::where('external_event_id', $this->inbound['external_event_id'])
            ->where('provider', $this->inbound['source'])
            ->first();

        if ($existing !== null) {
            $processandoAtivo = $existing->status === WebhookEvent::STATUS_PROCESSING
                && $existing->last_attempt_at?->gt(now()->subSeconds($this->timeout));

            if ($existing->status === WebhookEvent::STATUS_COMPLETED || $processandoAtivo) {
                Log::channel('webhooks')->info('Webhook already processed (idempotency)', [
                    'external_event_id' => $this->inbound['external_event_id'],
                    'provider' => $this->inbound['source'],
                    'status' => $existing->status,
                    'trace_id' => $this->inbound['trace_id'],
                ]);

                return null;
            }
        }

        return WebhookEvent::updateOrCreate(
            [
                'external_event_id' => $this->inbound['external_event_id'],
                'provider' => $this->inbound['source'],
            ],
            [
                'event_type' => $this->inbound['event_type'],
                'payload' => $this->inbound['payload'],
                'status' => WebhookEvent::STATUS_PENDING,
            ]
        );
    }

    /**
     * Handler de falha permanente
     * Move para Dead Letter Queue apos todas as tentativas
     */
    public function failed(Throwable $exception): void
    {
        if ($this->event === null) {
            Log::channel('webhooks')->error('Inbound webhook failed before event resolution', [
                'external_event_id' => $this->inbound['external_event_id'] ?? null,
                'provider' => $this->inbound['source'] ?? null,
                'error' => $exception->getMessage(),
            ]);

            return;
        }

        $this->event->markAsFailed($exception->getMessage());

        $isDeadLetter = $this->event->status === WebhookEvent::STATUS_DEAD_LETTER;

        Log::channel('webhooks')->error('Inbound webhook permanently failed', [
            'event_id' => $this->event->id,
            'external_event_id' => $this->event->external_event_id,
            'provider' => $this->event->provider,
            'attempts' => $this->event->attempts,
            'error' => $exception->getMessage(),
            'moved_to_dlq' => $isDeadLetter,
        ]);

        // Notifica sobre dead letter se configurado
        if ($isDeadLetter) {
            Log::channel('webhooks')->critical('Webhook moved to Dead Letter Queue', [
                'event_id' => $this->event->id,
                'provider' => $this->event->provider,
                'type' => $this->event->event_type,
            ]);
        }
    }

    /**
     * Processa webhook baseado no tipo
     * Extensivel para novos tipos de eventos
     */
    protected function processWebhookByType(array $payload, ?string $type): array
    {
        return match($type) {
            'payment.completed' => $this->handlePaymentCompleted($payload),
            'payment.failed' => $this->handlePaymentFailed($payload),
            'user.created' => $this->handleUserCreated($payload),
            'user.updated' => $this->handleUserUpdated($payload),
            'data.sync' => $this->handleDataSync($payload),
            'alert.triggered' => $this->handleAlertTriggered($payload),
            default => $this->handleGenericWebhook($payload, $type),
        };
    }

    // Handlers especificos - implemente conforme necessidade
    protected function handlePaymentCompleted(array $payload): array
    {
        return ['status' => 'processed', 'type' => 'payment.completed'];
    }

    protected function handlePaymentFailed(array $payload): array
    {
        return ['status' => 'processed', 'type' => 'payment.failed'];
    }

    protected function handleUserCreated(array $payload): array
    {
        return ['status' => 'processed', 'type' => 'user.created'];
    }

    protected function handleUserUpdated(array $payload): array
    {
        return ['status' => 'processed', 'type' => 'user.updated'];
    }

    protected function handleDataSync(array $payload): array
    {
        return ['status' => 'processed', 'type' => 'data.sync'];
    }

    protected function handleAlertTriggered(array $payload): array
    {
        return ['status' => 'processed', 'type' => 'alert.triggered'];
    }

    protected function handleGenericWebhook(array $payload, ?string $type): array
    {
        return ['status' => 'processed', 'type' => $type ?? 'generic'];
    }
}
