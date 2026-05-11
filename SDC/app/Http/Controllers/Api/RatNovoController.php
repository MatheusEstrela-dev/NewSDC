<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Rat\RatNovoService;
use App\Modules\Rat\Http\Resources\RatResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="RAT Novo",
 *     description="Endpoints da API para a nova estrutura de RAT (RatOcorrencia + relatos polimórficos)"
 * )
 *
 * @OA\Schema(
 *     schema="RatNovo",
 *     type="object",
 *     title="Nova Estrutura de RAT",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="protocolo", type="string", example="RAT-2026-0001"),
 *     @OA\Property(property="status", type="integer", example=0, description="0=Rascunho, 1=Finalizado"),
 *     @OA\Property(property="status_label", type="string", example="Rascunho"),
 *     @OA\Property(property="tem_vistoria", type="boolean", example=false),
 *     @OA\Property(property="dados_gerais", type="object"),
 *     @OA\Property(property="local", type="object"),
 *     @OA\Property(property="recursos", type="array", @OA\Items(type="object")),
 *     @OA\Property(property="envolvidos", type="array", @OA\Items(type="object")),
 *     @OA\Property(property="criado_por", type="string", example="João Silva"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="RatNovoList",
 *     type="object",
 *     @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/RatNovo")),
 *     @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta"),
 *     @OA\Property(property="links", ref="#/components/schemas/PaginationLinks")
 * )
 *
 * @OA\Schema(
 *     schema="RatNovoStoreRequest",
 *     type="object",
 *     required={"numero_bos"},
 *     @OA\Property(property="numero_bos", type="string", example="BOS-2026-001"),
 *     @OA\Property(property="status", type="integer", enum={0, 1}, default=0),
 *     @OA\Property(
 *         property="dados_gerais",
 *         type="object",
 *         @OA\Property(property="nat_codigo", type="string", example="1.01.00"),
 *         @OA\Property(property="local_municipio", type="string", example="Florianópolis"),
 *         @OA\Property(property="local_estadouf", type="string", example="SC")
 *     )
 * )
 */
class RatNovoController extends Controller
{
    public function __construct(
        private readonly RatNovoService $service
    ) {}

    /**
     * @OA\Get(
     *     path="/api/v1/rat-novo/{id}",
     *     summary="Retorna dados completos e normalizados de uma ocorrência",
     *     tags={"RAT Novo"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Dados do RAT",
     *         @OA\JsonContent(ref="#/components/schemas/RatNovo")
     *     ),
     *     @OA\Response(response=404, description="RAT não encontrado")
     * )
     */
    public function show(int $id): RatResource
    {
        $ocorrencia = \App\Modules\Rat\Models\RatOcorrencia::with('relatosMorph')->findOrFail($id);
        return new RatResource($ocorrencia);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/rat-novo/{id}/power-bi",
     *     summary="Payload otimizado para consumo direto pelo Power BI",
     *     tags={"RAT Novo"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Dados otimizados para BI"
     *     )
     * )
     */
    public function powerBiData(int $id): JsonResponse
    {
        $ocorrencia = \App\Modules\Rat\Models\RatOcorrencia::with('relatosMorph')->findOrFail($id);

        return response()->json([
            'dados_gerais' => $this->service->extractDadosGerais($ocorrencia),
            'envolvidos'   => $this->service->extractEnvolvidos($ocorrencia),
            'recursos'     => $this->service->extractRecursos($ocorrencia),
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/rat-novo",
     *     summary="Lista resumida de ocorrências para integrações externas",
     *     tags={"RAT Novo"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de RATs",
     *         @OA\JsonContent(ref="#/components/schemas/RatNovoList")
     *     )
     * )
     */
    public function index(): AnonymousResourceCollection
    {
        $ocorrencias = \App\Modules\Rat\Models\RatOcorrencia::latest()
            ->paginate(request()->integer('per_page', 15));

        return RatResource::collection($ocorrencias);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/rat-novo",
     *     summary="Cria um novo registro de RAT",
     *     tags={"RAT Novo"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/RatNovoStoreRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="RAT criado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", ref="#/components/schemas/RatNovo")
     *         )
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'numero_bos'   => 'nullable|string|max:50',
            'status'       => 'integer|in:0,1',
            'dados_gerais' => 'nullable|array',
        ]);

        $ocorrencia = $this->service->createRat($validated);

        return response()->json([
            'message' => 'RAT criado com sucesso!',
            'data'    => new RatResource($ocorrencia)
        ], 201);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/rat-novo/{id}",
     *     summary="Atualiza dados básicos do RAT",
     *     tags={"RAT Novo"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="numero_bos", type="string"),
     *             @OA\Property(property="status", type="integer"),
     *             @OA\Property(property="historico", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="RAT atualizado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", ref="#/components/schemas/RatNovo")
     *         )
     *     )
     * )
     */
    public function update(int $id, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'numero_bos'   => 'nullable|string|max:50',
            'status'       => 'integer|in:0,1',
            'historico'    => 'nullable|string',
        ]);

        $ocorrencia = $this->service->updateRat($id, $validated);

        return response()->json([
            'message' => 'RAT atualizado com sucesso!',
            'data'    => new RatResource($ocorrencia)
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/rat-novo/{id}",
     *     summary="Remove o registro (soft delete)",
     *     tags={"RAT Novo"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="RAT removido com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        $this->service->deleteRat($id);

        return response()->json([
            'message' => 'RAT removido com sucesso!'
        ]);
    }
}
