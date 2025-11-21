<?php

namespace App\Http\Controllers\Api\V1\PowerBI;

use App\Http\Controllers\Controller;
use App\Services\IntegrationTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * @OA\Tag(
 *     name="Power BI",
 *     description="Endpoints para integração com Power BI e geração de tokens para múltiplas APIs"
 * )
 */
class ProxyController extends Controller
{
    protected IntegrationTokenService $tokenService;

    public function __construct(IntegrationTokenService $tokenService)
    {
        $this->tokenService = $tokenService;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/power-bi/proxy/{api}/{path}",
     *     summary="Proxy para acessar APIs externas usando token Power BI",
     *     description="Faz proxy de requisições para APIs externas. O sistema valida o token Power BI, busca o token individual da API e faz a requisição automaticamente.",
     *     operationId="proxyRequest",
     *     tags={"Power BI"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="api",
     *         in="path",
     *         description="Nome da API (pae, rat, tdap, bi)",
     *         required=true,
     *         @OA\Schema(type="string", enum={"pae", "rat", "tdap", "bi"})
     *     ),
     *     @OA\Parameter(
     *         name="path",
     *         in="path",
     *         description="Caminho do endpoint na API externa (ex: api/v1/empreendimentos)",
     *         required=true,
     *         @OA\Schema(type="string", example="api/v1/empreendimentos")
     *     ),
     *     @OA\Parameter(
     *         name="X-PowerBI-Token",
     *         in="header",
     *         description="Token do Power BI",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Resposta da API externa",
     *         @OA\JsonContent()
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Token inválido ou não fornecido"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="API não encontrada"
     *     )
     *     )
     * )
     */
    public function proxy(Request $request, string $api, string $path): JsonResponse
    {
        // Obtém token Power BI do header
        $powerBIToken = $request->header('X-PowerBI-Token') 
            ?? $request->bearerToken();

        if (!$powerBIToken) {
            return response()->json([
                'success' => false,
                'message' => 'Token Power BI não fornecido. Use o header X-PowerBI-Token ou Authorization Bearer.',
            ], 401);
        }

        // Valida token Power BI e obtém tokens individuais
        $apiTokens = $this->tokenService->validatePowerBIToken($powerBIToken);

        if (!$apiTokens) {
            return response()->json([
                'success' => false,
                'message' => 'Token Power BI inválido ou expirado.',
            ], 401);
        }

        // Verifica se a API solicitada está disponível
        if (!isset($apiTokens[$api])) {
            return response()->json([
                'success' => false,
                'message' => "API '{$api}' não está disponível neste token Power BI.",
            ], 404);
        }

        $apiConfig = config("integrations.apis.{$api}");
        if (!$apiConfig) {
            return response()->json([
                'success' => false,
                'message' => "API '{$api}' não configurada.",
            ], 404);
        }

        // Prepara URL completa
        $baseUrl = rtrim($apiConfig['base_url'], '/');
        $endpoint = ltrim($path, '/');
        $fullUrl = "{$baseUrl}/{$endpoint}";

        // Obtém token individual da API
        $apiToken = $apiTokens[$api]['token'];

        try {
            // Prepara a requisição HTTP
            $httpRequest = Http::withHeaders([
                'Authorization' => "Bearer {$apiToken}",
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ]);

            // Adiciona query parameters
            if ($request->query()->count() > 0) {
                $httpRequest = $httpRequest->withQueryParameters($request->query()->all());
            }

            // Faz a requisição baseada no método
            $method = strtolower($request->method());
            $body = $request->json()->all() ?: $request->all();

            $response = match($method) {
                'get' => $httpRequest->get($fullUrl),
                'post' => $httpRequest->post($fullUrl, $body),
                'put' => $httpRequest->put($fullUrl, $body),
                'patch' => $httpRequest->patch($fullUrl, $body),
                'delete' => $httpRequest->delete($fullUrl, $body),
                default => $httpRequest->get($fullUrl),
            };

            // Retorna resposta da API externa
            return response()->json(
                $response->json(),
                $response->status(),
                $response->headers()
            );
        } catch (\Exception $e) {
            Log::error("Erro ao fazer proxy para API {$api}: " . $e->getMessage(), [
                'url' => $fullUrl,
                'method' => $request->method(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao acessar API externa: ' . $e->getMessage(),
            ], 500);
        }
    }
}

