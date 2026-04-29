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
 * Endpoints da API para a nova estrutura de RAT (RatOcorrencia + relatos polimórficos).
 *
 * Métodos públicos: index · show · powerBiData
 */
class RatNovoController extends Controller
{
    public function __construct(
        private readonly RatNovoService $service
    ) {}

    /**
     * GET /api/rat-novo/{id}
     * Retorna dados completos e normalizados de uma ocorrência.
     */
    public function show(int $id): RatResource
    {
        $ocorrencia = \App\Models\Rat\RatOcorrencia::with('relatosMorph')->findOrFail($id);
        return new RatResource($ocorrencia);
    }

    /**
     * GET /api/rat-novo/{id}/power-bi
     * Payload otimizado para consumo direto pelo Power BI.
     */
    public function powerBiData(int $id): JsonResponse
    {
        $ocorrencia = \App\Models\Rat\RatOcorrencia::with('relatosMorph')->findOrFail($id);

        return response()->json([
            'dados_gerais' => $this->service->extractDadosGerais($ocorrencia),
            'envolvidos'   => $this->service->extractEnvolvidos($ocorrencia),
            'recursos'     => $this->service->extractRecursos($ocorrencia),
        ]);
    }

    /**
     * GET /api/rat-novo
     * Lista resumida de ocorrências para integrações externas.
     */
    public function index(): AnonymousResourceCollection
    {
        $ocorrencias = \App\Models\Rat\RatOcorrencia::latest()
            ->paginate(request()->integer('per_page', 15));

        return RatResource::collection($ocorrencias);
    }

    /**
     * POST /api/rat-novo
     * Cria um novo registro de RAT.
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
        ], 21);
    }

    /**
     * PUT /api/rat-novo/{id}
     * Atualiza dados básicos do RAT.
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
     * DELETE /api/rat-novo/{id}
     * Remove o registro (soft delete).
     */
    public function destroy(int $id): JsonResponse
    {
        $this->service->deleteRat($id);

        return response()->json([
            'message' => 'RAT removido com sucesso!'
        ]);
    }
}
