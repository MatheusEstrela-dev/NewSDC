<?php

namespace App\Http\Controllers\Api\V1\Webhook;

use App\Http\Controllers\Controller;
use App\Services\Webhook\WebhookService;
use App\Services\Webhook\WebhookSignatureValidator;
use App\Enums\RequestPriority;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Tag(
 *     name="Webhooks",
 *     description="API Endpoints para gerenciamento de webhooks"
 * )
 */
class WebhookController extends Controller
{
    public function __construct(
        private WebhookService $webhookService,
        private WebhookSignatureValidator $signatureValidator
    ) {}

    /**
     * @OA\Post(
     *     path="/api/v1/webhooks/receive",
     *     summary="Receber webhook de sistema externo",
     *     description="Endpoint para receber webhooks de sistemas externos com validação de segurança.<br><br>
     *                  **Arquitetura Assíncrona (Fire & Forget):**<br>
     *                  - Valida o básico (schema OpenAPI)<br>
     *                  - Enfileira para processamento (Redis Queue)<br>
     *                  - Responde imediatamente com 202 Accepted<br>
     *                  - Cliente usa o `trace_id` para consultar status depois<br><br>
     *                  **Headers Opcionais:**<br>
     *                  - `X-Webhook-Source`: Identificação do sistema origem<br>
     *                  - `X-Webhook-Signature`: Assinatura HMAC SHA-256<br>
     *                  - `Idempotency-Key`: UUID v4 para evitar duplicatas",
     *     operationId="receiveWebhook",
     *     tags={"Webhooks"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Payload do webhook",
     *         @OA\JsonContent(
     *             required={"type", "data"},
     *             @OA\Property(property="type", type="string", example="payment.completed", description="Tipo do evento"),
     *             @OA\Property(property="data", type="object", description="Dados do evento"),
     *             @OA\Property(property="timestamp", type="string", format="date-time", example="2025-11-27T10:00:00Z"),
     *             @OA\Property(property="signature", type="string", description="Assinatura HMAC do webhook")
     *         )
     *     ),
     *     @OA\Response(
     *         response=202,
     *         description="Webhook aceito e enfileirado para processamento",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="accepted"),
     *             @OA\Property(property="message", type="string", example="Webhook queued for processing"),
     *             @OA\Property(property="trace_id", type="string", example="9d7f8e2a-3c1b-4567-8901-23456789abcd", description="UUID para rastreamento"),
     *             @OA\Property(property="webhook_id", type="string", example="wh_1234567890"),
     *             @OA\Property(property="estimated_processing", type="string", example="within 30 seconds")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Dados inválidos",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Invalid Request"),
     *             @OA\Property(property="message", type="string", example="Invalid webhook signature")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Schema Validation Failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Schema Validation Failed"),
     *             @OA\Property(property="violations", type="array", @OA\Items(type="string"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=429,
     *         description="Rate Limit Exceeded",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Rate Limit Exceeded"),
     *             @OA\Property(property="retry_after_seconds", type="integer", example=60),
     *             @OA\Property(property="limit", type="integer", example=10000),
     *             @OA\Property(property="cost_per_request", type="number", example=1)
     *         )
     *     )
     * )
     */
    public function receive(Request $request): JsonResponse
    {
        // 1. Geração de um ID de rastreio (Correlation ID / Trace ID)
        // Permite rastrear todo o fluxo do webhook do início ao fim
        $traceId = \Illuminate\Support\Str::uuid()->toString();

        // Validacao de assinatura HMAC: garante que o payload veio de fonte
        // confiavel (endpoint e publico/sem auth:sanctum). Assina-se o corpo raw
        // com o secret compartilhado (config webhooks.providers.default.secret).
        $webhookSecret = config('webhooks.providers.default.secret');
        if ($webhookSecret) {
            $signature = (string) $request->header('X-Webhook-Signature', '');
            if ($signature === '' || ! $this->signatureValidator->validate($request->getContent(), $signature, 'default')) {
                return response()->json([
                    'error'    => 'Invalid Request',
                    'message'  => 'Assinatura de webhook invalida ou ausente.',
                    'trace_id' => $traceId,
                ], 401);
            }
        } elseif (app()->environment('production')) {
            // Fail-closed: em producao o secret e obrigatorio para nao expor
            // ingestao anonima.
            return response()->json([
                'error'    => 'Server Misconfiguration',
                'message'  => 'Webhook secret nao configurado.',
                'trace_id' => $traceId,
            ], 503);
        }

        $validated = $request->validate([
            'type' => 'required|string|max:100',
            'data' => 'required|array',
            'timestamp' => 'nullable|date',
            'signature' => 'nullable|string',
        ]);

        try {
            $source = $request->header('X-Webhook-Source', 'unknown');

            // 2. Envia para a fila (Isso leva ~2ms)
            // Não processa na hora - é Fire & Forget. O header Idempotency-Key
            // (documentado no Swagger) tem prioridade como chave de deduplicacao.
            $this->webhookService->receive($validated, $source, $traceId, $request->header('Idempotency-Key'));

            // 3. Retorna 202 (Aceito) - NÃO retorna 200 (OK/Feito)
            // Contexto: O cliente recebe a resposta em ~50ms,
            // mesmo que o processamento demore 10 minutos
            return response()->json([
                'status' => 'accepted',
                'message' => 'Webhook queued for processing',
                'trace_id' => $traceId, // Cliente usa esse ID para consultar o status depois
                'webhook_id' => uniqid('wh_'),
                'estimated_processing' => 'within 30 seconds',
            ], 202); // 202 Accepted - padrão correto para assíncrono

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Invalid Request',
                'message' => $e->getMessage(),
                'trace_id' => $traceId,
            ], 400);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/webhooks/send",
     *     summary="Enviar webhook para sistema externo",
     *     description="Envia webhook de forma assíncrona usando filas Redis. Suporta priorização e retry automático.",
     *     operationId="sendWebhook",
     *     tags={"Webhooks"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Dados do webhook a ser enviado",
     *         @OA\JsonContent(
     *             required={"url", "payload"},
     *             @OA\Property(property="url", type="string", format="url", example="https://example.com/webhook"),
     *             @OA\Property(property="payload", type="object", description="Dados a serem enviados"),
     *             @OA\Property(
     *                 property="priority",
     *                 type="string",
     *                 enum={"low", "normal", "high", "critical", "webhook"},
     *                 example="normal",
     *                 description="Prioridade de processamento"
     *             ),
     *             @OA\Property(
     *                 property="headers",
     *                 type="object",
     *                 description="Headers customizados",
     *                 example={"X-Custom-Header": "value"}
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=202,
     *         description="Webhook enfileirado para envio",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Webhook queued for delivery"),
     *             @OA\Property(property="priority", type="string", example="normal"),
     *             @OA\Property(property="estimated_delivery", type="string", example="within 30 seconds")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Dados de validação inválidos",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function send(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'url' => 'required|url',
            'payload' => 'required|array',
            'priority' => 'nullable|in:low,normal,high,critical,webhook',
            'headers' => 'nullable|array',
        ]);

        $priority = RequestPriority::from($validated['priority'] ?? 'normal');
        $userId = $request->user()?->id;

        $this->webhookService->send(
            $validated['url'],
            $validated['payload'],
            $validated['headers'] ?? [],
            $priority,
            $userId
        );

        return response()->json([
            'success' => true,
            'message' => 'Webhook queued for delivery',
            'priority' => $priority->value,
            'queue' => $priority->queue(),
            'estimated_delivery' => "within {$priority->timeout()} seconds",
        ], 202);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/webhooks/send-sync",
     *     summary="Enviar webhook síncrono (bloqueante)",
     *     description="Envia webhook de forma síncrona. Use apenas para testes ou casos críticos onde resposta imediata é necessária.",
     *     operationId="sendWebhookSync",
     *     tags={"Webhooks"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"url", "payload"},
     *             @OA\Property(property="url", type="string", format="url"),
     *             @OA\Property(property="payload", type="object"),
     *             @OA\Property(property="timeout", type="integer", example=30, description="Timeout em segundos"),
     *             @OA\Property(property="headers", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Webhook enviado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="status", type="integer", example=200),
     *             @OA\Property(property="body", type="object"),
     *             @OA\Property(property="duration_ms", type="number", example=145.67)
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erro ao enviar webhook",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function sendSync(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'url' => 'required|url',
            'payload' => 'required|array',
            'timeout' => 'nullable|integer|min:5|max:60',
            'headers' => 'nullable|array',
        ]);

        $result = $this->webhookService->sendSync(
            $validated['url'],
            $validated['payload'],
            $validated['headers'] ?? [],
            $validated['timeout'] ?? 30
        );

        return response()->json($result, $result['success'] ? 200 : 500);
    }
}
