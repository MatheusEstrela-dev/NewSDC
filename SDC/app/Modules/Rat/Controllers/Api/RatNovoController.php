<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Rat\RatNovoService;
use Illuminate\Http\JsonResponse;
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
    public function show(int $id, Request $request): JsonResponse
    {
        $request->merge(['ocorrencia_id' => $id]);
        $request->route() && $request->route()->setParameter('id', $id);

        $data = $this->service->getNormalizedDataForPowerBI($request);

        return response()->json($data);
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
    public function index(): JsonResponse
    {
        $ocorrencias = \App\Models\Rat\RatOcorrencia::latest()
            ->paginate(request()->integer('per_page', 15));

        return response()->json($ocorrencias);
    }
}
