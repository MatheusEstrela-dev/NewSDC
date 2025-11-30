<?php

namespace App\Http\Controllers\Api\V1\Webhook;

use App\Http\Controllers\Controller;
use App\Services\Webhook\WebhookService;
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
        private WebhookService $webhookService
    ) {}

    /**
     * @OA\Post(
     *     path="/api/v1/webhooks/receive",
     *     summary="Receber webhook de sistema externo",
     *     description="Endpoint para receber webhooks de sistemas externos com validação de segurança",
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
     *         response=200,
     *         description="Webhook processado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Webhook received and queued for processing"),
     *             @OA\Property(property="webhook_id", type="string", example="wh_1234567890")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Dados inválidos",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Invalid webhook signature")
     *         )
     *     ),
     *     @OA\Response(
     *         response=429,
     *         description="Muitas requisições",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Too many requests"),
     *             @OA\Property(property="retry_after", type="integer", example=60)
     *         )
     *     )
     * )
     */
    public function receive(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|string',
            'data' => 'required|array',
            'timestamp' => 'nullable|date',
            'signature' => 'nullable|string',
        ]);

        try {
            $source = $request->header('X-Webhook-Source', 'unknown');
            $result = $this->webhookService->receive($validated, $source);

            return response()->json([
                'success' => true,
                'message' => 'Webhook received and processed',
                'webhook_id' => uniqid('wh_'),
                'result' => $result,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
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
