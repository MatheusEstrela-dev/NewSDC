<?php

namespace App\Http\Controllers\Api\V1\Rat;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="RAT",
 *     description="Endpoints do módulo RAT (Registro de Atendimento Técnico)"
 * )
 * 
 * @OA\Schema(
 *     schema="ProtocoloRAT",
 *     type="object",
 *     title="Protocolo RAT",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="numero", type="string", example="2025/001"),
 *     @OA\Property(property="municipio_id", type="integer", example=123),
 *     @OA\Property(property="tipo", type="string", example="Vistoria Técnica"),
 *     @OA\Property(property="status", type="string", example="em_analise"),
 *     @OA\Property(property="data", type="string", format="date", example="2025-01-20")
 * )
 */
class ProtocoloController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/rat/protocolos",
     *     summary="Lista todos os protocolos RAT",
     *     description="Retorna uma lista paginada de todos os protocolos RAT cadastrados",
     *     operationId="listProtocolos",
     *     tags={"RAT"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Número da página",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de protocolos retornada com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/ProtocoloRAT")),
     *             @OA\Property(property="meta", type="object", ref="#/components/schemas/PaginationMeta")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'data' => [],
            'meta' => ['current_page' => 1, 'total' => 0],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/rat/protocolos/{id}",
     *     summary="Exibe um protocolo RAT específico",
     *     operationId="showProtocolo",
     *     tags={"RAT"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Protocolo encontrado",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", ref="#/components/schemas/ProtocoloRAT")
     *         )
     *     )
     * )
     */
    public function show(int $id): JsonResponse
    {
        return response()->json([
            'data' => ['id' => $id, 'numero' => '2025/001'],
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/rat/protocolos",
     *     summary="Cria um novo protocolo RAT",
     *     operationId="storeProtocolo",
     *     tags={"RAT"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"municipio_id", "tipo"},
     *             @OA\Property(property="municipio_id", type="integer", example=123),
     *             @OA\Property(property="tipo", type="string", example="Vistoria Técnica"),
     *             @OA\Property(property="descricao", type="string", example="Solicitação de vistoria técnica")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Protocolo criado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", ref="#/components/schemas/ProtocoloRAT"),
     *             @OA\Property(property="message", type="string", example="Protocolo criado com sucesso")
     *         )
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $request->all(),
            'message' => 'Protocolo criado com sucesso',
        ], 201);
    }
}

