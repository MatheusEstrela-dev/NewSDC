<?php

namespace App\Http\Controllers\Api\V1\PowerBI;

use App\Http\Controllers\Controller;
use App\Services\IntegrationTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Power BI",
 *     description="Endpoints para integração com Power BI e geração de tokens para múltiplas APIs"
 * )
 */
class TokenController extends Controller
{
    protected IntegrationTokenService $tokenService;

    public function __construct(IntegrationTokenService $tokenService)
    {
        $this->tokenService = $tokenService;
    }

    /**
     * @OA\Post(
     *     path="/api/v1/power-bi/token",
     *     summary="Gera token único para Power BI acessar múltiplas APIs",
     *     description="Gera um token único que permite ao Power BI acessar todas as APIs configuradas (PAE, RAT, TDAP, BI). Este token contém tokens individuais para cada API.",
     *     operationId="generatePowerBIToken",
     *     tags={"Power BI"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="apis", type="array", @OA\Items(type="string", enum={"pae", "rat", "tdap", "bi"}), description="Lista de APIs a incluir no token. Se não informado, inclui todas as APIs permitidas."),
     *             @OA\Property(property="refresh", type="boolean", example=false, description="Força a renovação de todos os tokens")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Token gerado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="token", type="string", example="a1b2c3d4e5f6...", description="Token único para Power BI"),
     *                 @OA\Property(property="expires_in", type="integer", example=3600, description="Tempo de expiração em segundos"),
     *                 @OA\Property(property="apis", type="array", @OA\Items(type="string"), example={"pae", "rat", "tdap", "bi"}),
     *                 @OA\Property(property="endpoints", type="object",
     *                     @OA\Property(property="pae", type="object",
     *                         @OA\Property(property="url", type="string", example="https://api-pae.sdc.mg.gov.br"),
     *                         @OA\Property(property="name", type="string", example="API PAE")
     *                     ),
     *                     @OA\Property(property="rat", type="object",
     *                         @OA\Property(property="url", type="string", example="https://api-rat.sdc.mg.gov.br"),
     *                         @OA\Property(property="name", type="string", example="API RAT")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Não autenticado"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erro ao gerar token"
     *     )
     *     )
     * )
     */
    public function generateToken(Request $request): JsonResponse
    {
        $allowedApis = $request->input('apis');
        $forceRefresh = $request->boolean('refresh', false);

        try {
            // Se refresh forçado, limpa cache
            if ($forceRefresh) {
                $this->tokenService->clearAllTokenCache();
            }

            $tokenData = $this->tokenService->generatePowerBIToken($allowedApis);

            return response()->json([
                'success' => true,
                'data' => $tokenData,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao gerar token: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/power-bi/token/{token}",
     *     summary="Valida e retorna informações de um token do Power BI",
     *     description="Valida um token do Power BI e retorna os tokens individuais para cada API",
     *     operationId="validatePowerBIToken",
     *     tags={"Power BI"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="token",
     *         in="path",
     *         description="Token do Power BI",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Token válido",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="valid", type="boolean", example=true),
     *                 @OA\Property(property="apis", type="object", description="Tokens individuais para cada API")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Token inválido ou expirado"
     *     )
     *     )
     * )
     */
    public function validateToken(string $token): JsonResponse
    {
        $apiTokens = $this->tokenService->validatePowerBIToken($token);

        if (!$apiTokens) {
            return response()->json([
                'success' => false,
                'message' => 'Token inválido ou expirado',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'valid' => true,
                'apis' => $apiTokens,
            ],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/power-bi/tokens",
     *     summary="Lista tokens individuais para cada API",
     *     description="Retorna tokens individuais para cada API configurada, útil para testes",
     *     operationId="listApiTokens",
     *     tags={"Power BI"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="apis",
     *         in="query",
     *         description="Lista de APIs separadas por vírgula (pae,rat,tdap,bi)",
     *         required=false,
     *         @OA\Schema(type="string", example="pae,rat,bi")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tokens obtidos com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="pae", type="string", example="token_pae_123..."),
     *                 @OA\Property(property="rat", type="string", example="token_rat_456...")
     *             )
     *         )
     *     )
     *     )
     * )
     */
    public function listTokens(Request $request): JsonResponse
    {
        $apiKeys = $request->input('apis');
        
        if ($apiKeys) {
            $apiKeys = is_array($apiKeys) ? $apiKeys : explode(',', $apiKeys);
            $apiKeys = array_map('trim', $apiKeys);
        } else {
            $apiKeys = array_keys(config('integrations.apis', []));
        }

        $tokens = $this->tokenService->getMultipleTokens($apiKeys);

        return response()->json([
            'success' => true,
            'data' => $tokens,
        ]);
    }
}

