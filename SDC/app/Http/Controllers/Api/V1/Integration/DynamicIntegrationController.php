<?php

namespace App\Http\Controllers\Api\V1\Integration;

use App\Http\Controllers\Controller;
use App\Services\Integration\IntegrationHubService;
use App\Enums\RequestPriority;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Tag(
 *     name="Dynamic Integration",
 *     description="Hub de integração plug-and-play com sistemas externos. Suporte bidirecional para enviar e receber dados simultaneamente."
 * )
 */
class DynamicIntegrationController extends Controller
{
    public function __construct(
        private IntegrationHubService $integrationHub
    ) {}

    /**
     * @OA\Post(
     *     path="/api/v1/integration/execute",
     *     summary="Executar integração bidirecional",
     *     description="Endpoint universal para executar integrações com sistemas externos. Envia dados e recebe resposta em tempo real ou assíncrono.",
     *     operationId="executeIntegration",
     *     tags={"Dynamic Integration"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Dados da integração",
     *         @OA\JsonContent(
     *             required={"integration_type", "action", "payload"},
     *             @OA\Property(
     *                 property="integration_type",
     *                 type="string",
     *                 enum={"rest_api", "soap", "graphql", "webhook", "database", "file_transfer"},
     *                 example="rest_api",
     *                 description="Tipo de integração"
     *             ),
     *             @OA\Property(
     *                 property="action",
     *                 type="string",
     *                 example="create_user",
     *                 description="Ação a ser executada"
     *             ),
     *             @OA\Property(
     *                 property="endpoint",
     *                 type="string",
     *                 format="url",
     *                 example="https://api.external-system.com/v1/users",
     *                 description="URL do endpoint externo"
     *             ),
     *             @OA\Property(
     *                 property="method",
     *                 type="string",
     *                 enum={"GET", "POST", "PUT", "PATCH", "DELETE"},
     *                 example="POST"
     *             ),
     *             @OA\Property(
     *                 property="payload",
     *                 type="object",
     *                 description="Dados a serem enviados",
     *                 example={"name": "João Silva", "email": "joao@example.com"}
     *             ),
     *             @OA\Property(
     *                 property="headers",
     *                 type="object",
     *                 description="Headers customizados",
     *                 example={"X-API-Key": "abc123", "X-Client-ID": "xyz789"}
     *             ),
     *             @OA\Property(
     *                 property="auth",
     *                 type="object",
     *                 description="Configuração de autenticação",
     *                 @OA\Property(property="type", type="string", enum={"bearer", "basic", "oauth2", "api_key"}, example="bearer"),
     *                 @OA\Property(property="token", type="string", example="eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...")
     *             ),
     *             @OA\Property(
     *                 property="mapping",
     *                 type="object",
     *                 description="Mapeamento de campos (input → output)",
     *                 example={"internal_field": "external_field", "user_name": "name"}
     *             ),
     *             @OA\Property(
     *                 property="priority",
     *                 type="string",
     *                 enum={"low", "normal", "high", "critical"},
     *                 example="normal"
     *             ),
     *             @OA\Property(
     *                 property="async",
     *                 type="boolean",
     *                 example=false,
     *                 description="Se true, executa assíncrono via fila"
     *             ),
     *             @OA\Property(
     *                 property="bidirectional",
     *                 type="boolean",
     *                 example=true,
     *                 description="Se true, espera resposta e processa dados recebidos"
     *             ),
     *             @OA\Property(
     *                 property="callback_url",
     *                 type="string",
     *                 format="url",
     *                 example="https://meu-sistema.com/callback",
     *                 description="URL para receber resposta (apenas async)"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Integração executada com sucesso (síncrono)",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Integration executed successfully"),
     *             @OA\Property(property="integration_id", type="string", example="int_1234567890"),
     *             @OA\Property(property="sent_data", type="object", description="Dados enviados"),
     *             @OA\Property(property="received_data", type="object", description="Dados recebidos do sistema externo"),
     *             @OA\Property(property="mapped_response", type="object", description="Resposta mapeada conforme configuração"),
     *             @OA\Property(property="duration_ms", type="number", example=234.56),
     *             @OA\Property(property="external_status", type="integer", example=201)
     *         )
     *     ),
     *     @OA\Response(
     *         response=202,
     *         description="Integração enfileirada (assíncrono)",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Integration queued for processing"),
     *             @OA\Property(property="integration_id", type="string", example="int_1234567890"),
     *             @OA\Property(property="queue", type="string", example="high"),
     *             @OA\Property(property="estimated_execution", type="string", example="within 30 seconds")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erro de validação",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erro na integração",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="error", type="string", example="External API returned error 500"),
     *             @OA\Property(property="external_response", type="object")
     *         )
     *     )
     * )
     */
    public function execute(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'integration_type' => 'required|in:rest_api,soap,graphql,webhook,database,file_transfer',
            'action' => 'required|string',
            'endpoint' => 'required_if:integration_type,rest_api,soap,graphql|url',
            'method' => 'required_if:integration_type,rest_api|in:GET,POST,PUT,PATCH,DELETE',
            'payload' => 'required|array',
            'headers' => 'nullable|array',
            'auth' => 'nullable|array',
            'auth.type' => 'required_with:auth|in:bearer,basic,oauth2,api_key',
            'auth.token' => 'required_with:auth|string',
            'mapping' => 'nullable|array',
            'priority' => 'nullable|in:low,normal,high,critical',
            'async' => 'nullable|boolean',
            'bidirectional' => 'nullable|boolean',
            'callback_url' => 'nullable|url',
        ]);

        $priority = RequestPriority::from($validated['priority'] ?? 'normal');
        $async = $validated['async'] ?? false;
        $userId = $request->user()?->id;

        // Execução assíncrona (via fila)
        if ($async) {
            $integrationId = $this->integrationHub->queueIntegration($validated, $priority, $userId);

            return response()->json([
                'success' => true,
                'message' => 'Integration queued for processing',
                'integration_id' => $integrationId,
                'queue' => $priority->queue(),
                'estimated_execution' => "within {$priority->timeout()} seconds",
            ], 202);
        }

        // Execução síncrona (tempo real)
        try {
            $result = $this->integrationHub->executeSync($validated, $userId);

            return response()->json([
                'success' => true,
                'message' => 'Integration executed successfully',
                'integration_id' => $result['integration_id'],
                'sent_data' => $result['sent_data'],
                'received_data' => $result['received_data'],
                'mapped_response' => $result['mapped_response'],
                'duration_ms' => $result['duration_ms'],
                'external_status' => $result['status'],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'external_response' => $e->getTrace()[0] ?? null,
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/integration/status/{integrationId}",
     *     summary="Verificar status de integração",
     *     description="Consulta o status de uma integração assíncrona",
     *     operationId="checkIntegrationStatus",
     *     tags={"Dynamic Integration"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="integrationId",
     *         in="path",
     *         required=true,
     *         description="ID da integração",
     *         @OA\Schema(type="string", example="int_1234567890")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Status da integração",
     *         @OA\JsonContent(
     *             @OA\Property(property="integration_id", type="string", example="int_1234567890"),
     *             @OA\Property(property="status", type="string", enum={"pending", "processing", "completed", "failed"}, example="completed"),
     *             @OA\Property(property="result", type="object", description="Resultado (se completado)"),
     *             @OA\Property(property="error", type="string", nullable=true, description="Erro (se falhou)"),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="completed_at", type="string", format="date-time", nullable=true)
     *         )
     *     )
     * )
     */
    public function status(string $integrationId): JsonResponse
    {
        $status = $this->integrationHub->getStatus($integrationId);

        return response()->json($status);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/integration/templates",
     *     summary="Listar templates de integração pré-configurados",
     *     description="Retorna templates prontos para integrações com sistemas populares (Salesforce, SAP, etc)",
     *     operationId="listIntegrationTemplates",
     *     tags={"Dynamic Integration"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Lista de templates",
     *         @OA\JsonContent(
     *             @OA\Property(property="templates", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="string", example="salesforce_create_lead"),
     *                     @OA\Property(property="name", type="string", example="Salesforce - Criar Lead"),
     *                     @OA\Property(property="description", type="string"),
     *                     @OA\Property(property="integration_type", type="string", example="rest_api"),
     *                     @OA\Property(property="required_fields", type="array", @OA\Items(type="string")),
     *                     @OA\Property(property="example_payload", type="object")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function templates(): JsonResponse
    {
        $templates = $this->integrationHub->getTemplates();

        return response()->json([
            'templates' => $templates,
        ]);
    }
}
