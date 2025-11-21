<?php

namespace App\Http\Controllers\Api\V1\Integracao;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Tag(
 *     name="Integração",
 *     description="Endpoints para integração entre módulos (PAE, RAT, TDAP)"
 * )
 */
class IntegracaoController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/integracao/rat/{ratId}/pae",
     *     summary="Busca empreendimento PAE relacionado a um protocolo RAT",
     *     description="Retorna o empreendimento PAE relacionado a um protocolo RAT através do município ou referência",
     *     operationId="getPaeByRat",
     *     tags={"Integração"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="ratId",
     *         in="path",
     *         description="ID do protocolo RAT",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Dados relacionados encontrados",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="rat", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="numero", type="string", example="2025/001")
     *                 ),
     *                 @OA\Property(property="pae", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="nome", type="string", example="Barragem Sul Superior")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Nenhum relacionamento encontrado"
     *     )
     * )
     */
    public function getPaeByRat(int $ratId): JsonResponse
    {
        return response()->json([
            'data' => [
                'rat' => ['id' => $ratId, 'numero' => '2025/001'],
                'pae' => ['id' => 1, 'nome' => 'Barragem Sul Superior'],
            ],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/integracao/pae/{paeId}/rat",
     *     summary="Busca protocolos RAT relacionados a um empreendimento PAE",
     *     description="Retorna todos os protocolos RAT relacionados a um empreendimento PAE",
     *     operationId="getRatByPae",
     *     tags={"Integração"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="paeId",
     *         in="path",
     *         description="ID do empreendimento PAE",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Protocolos relacionados encontrados",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="pae", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="nome", type="string", example="Barragem Sul Superior")
     *                 ),
     *                 @OA\Property(property="protocolos_rat", type="array", @OA\Items(type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="numero", type="string", example="2025/001")
     *                 )),
     *                 @OA\Property(property="total", type="integer", example=3)
     *             )
     *         )
     *     )
     * )
     */
    public function getRatByPae(int $paeId): JsonResponse
    {
        return response()->json([
            'data' => [
                'pae' => ['id' => $paeId, 'nome' => 'Barragem Sul Superior'],
                'protocolos_rat' => [],
                'total' => 0,
            ],
        ]);
    }
}

