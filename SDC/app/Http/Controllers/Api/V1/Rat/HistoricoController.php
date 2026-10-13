<?php

namespace App\Http\Controllers\Api\V1\Rat;

use App\Http\Controllers\Controller;
use App\Modules\Rat\Services\RatHistoricoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Schema(
 *     schema="RatHistoricoItem",
 *     type="object",
 *     description="Evento da trilha de alterações de uma ocorrência RAT",
 *     @OA\Property(property="id", type="string", format="uuid", example="0199a3b2-0000-7000-8000-0000000004b1"),
 *     @OA\Property(property="ocorrencia_id", type="string", format="uuid", example="0199a3b2-0000-7000-8000-000000000001"),
 *     @OA\Property(property="user_id", type="integer", nullable=true, example=17),
 *     @OA\Property(property="evento", type="string", description="Tipo do evento registrado", example="atualizado"),
 *     @OA\Property(property="payload", type="object", nullable=true, description="Dados do evento (campos alterados, valores anteriores e novos)"),
 *     @OA\Property(property="ip_address", type="string", nullable=true, example="10.0.0.12"),
 *     @OA\Property(property="user_agent", type="string", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2026-10-13T10:15:00-03:00")
 * )
 */
class HistoricoController extends Controller
{
    public function __construct(
        private readonly RatHistoricoService $historicoService,
    ) {}

    /**
     * @OA\Get(
     *     path="/api/v1/rat/ocorrencias/{id}/historico",
     *     operationId="ratHistoricoIndex",
     *     tags={"RAT"},
     *     summary="Histórico de alterações de uma ocorrência RAT (paginado)",
     *     description="Trilha de alterações da ocorrência, da mais recente para a mais antiga. Substitui o endpoint legado GET /bi/rat/changes/{ocorrenciaId}. Requer a permissão rat.historico.view.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="UUID da ocorrência RAT", @OA\Schema(type="string", format="uuid", example="0199a3b2-0000-7000-8000-000000000001")),
     *     @OA\Parameter(name="per_page", in="query", required=false, description="Itens por página (padrão: 20)", @OA\Schema(type="integer", example=20)),
     *     @OA\Parameter(name="page", in="query", required=false, description="Página", @OA\Schema(type="integer", example=1)),
     *     @OA\Response(
     *         response=200,
     *         description="Histórico paginado",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/RatHistoricoItem")),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="total", type="integer", example=42),
     *                 @OA\Property(property="current_page", type="integer", example=1)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Não autenticado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=403, description="Sem a permissão rat.historico.view", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function index(Request $request, string $id): JsonResponse
    {
        $timeline = $this->historicoService->timeline(
            $id,
            $request->integer('per_page', 20)
        );

        return response()->json([
            'data' => $timeline['data'],
            'meta' => [
                'total'        => $timeline['total'],
                'current_page' => $timeline['page'],
            ],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/rat/ocorrencias/{id}/historico/recent",
     *     operationId="ratHistoricoRecent",
     *     tags={"RAT"},
     *     summary="Últimos eventos do histórico de uma ocorrência RAT",
     *     description="Retorna os N eventos mais recentes da trilha de alterações, sem paginação. Requer a permissão rat.historico.view.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="UUID da ocorrência RAT", @OA\Schema(type="string", format="uuid", example="0199a3b2-0000-7000-8000-000000000001")),
     *     @OA\Parameter(name="limite", in="query", required=false, description="Quantidade de eventos (padrão: 10)", @OA\Schema(type="integer", example=10)),
     *     @OA\Response(
     *         response=200,
     *         description="Eventos mais recentes",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/RatHistoricoItem"))
     *         )
     *     ),
     *     @OA\Response(response=401, description="Não autenticado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=403, description="Sem a permissão rat.historico.view", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function recent(Request $request, string $id): JsonResponse
    {
        $items = $this->historicoService->recent(
            $id,
            $request->integer('limite', 10)
        );

        return response()->json(['data' => $items]);
    }
}
