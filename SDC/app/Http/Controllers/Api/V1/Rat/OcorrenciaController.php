<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Rat;

use App\DTOs\Rat\RatBoDTO;
use App\DTOs\Rat\RatOcorrenciaFiltroDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Rat\BoRequest;
use App\Http\Resources\Rat\RatOcorrenciaResource;
use App\Models\Rat\RatOcorrencia;
use App\Services\Rat\RatHistoricoService;
use App\Services\Rat\RatOcorrenciaService;
use App\Services\Rat\RatRelatoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * API REST para ocorrências RAT — consumida pela aplicação mobile e integrações externas.
 *
 * Autenticação via Sanctum (Token Bearer).
 * Responsabilidade única: expõe CRUD de RatOcorrencia com JSON padronizado.
 *
 * Métodos públicos: listar · salvar · exibir · atualizar · finalizar · remover
 */
class OcorrenciaController extends Controller
{
    public function __construct(
        private readonly RatOcorrenciaService $ocorrenciaService,
        private readonly RatRelatoService     $relatoService,
        private readonly RatHistoricoService  $historicoService,
    ) {}

    /**
     * GET /api/v1/rat/ocorrencias
     * Lista paginada com filtros opcionais: status, numero_bos, pagina, por_pagina.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filtro = RatOcorrenciaFiltroDTO::fromArray($request->only([
            'status',
            'numero_bos',
            'por_pagina',
        ]));

        $ocorrencias = $this->ocorrenciaService->paginate($filtro);

        return RatOcorrenciaResource::collection($ocorrencias);
    }

    /**
     * POST /api/v1/rat/ocorrencias
     * Registra uma nova ocorrência a partir do BO validado.
     */
    public function store(BoRequest $request): JsonResponse
    {
        $ocorrencia = $this->ocorrenciaService->manageOcorrencia(
            RatBoDTO::fromArray($request->validated())
        );

        if ($request->has('relatos')) {
            $this->relatoService->manageRelatos($ocorrencia, $request->input('relatos'));
        }

        $this->historicoService->log($ocorrencia, 'ocorrencia.registrada_mobile');

        return (new RatOcorrenciaResource($ocorrencia->load('relatosMorph')))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * GET /api/v1/rat/ocorrencias/{id}
     * Retorna uma ocorrência com todos os relatos polimórficos.
     */
    public function show(int $id): JsonResponse
    {
        $ocorrencia = $this->ocorrenciaService->findOrFail($id);

        return (new RatOcorrenciaResource($ocorrencia))
            ->response()
            ->setStatusCode(200);
    }

    /**
     * PUT /api/v1/rat/ocorrencias/{id}
     * Atualiza dados da ocorrência (número BOS, histórico, prazo).
     */
    public function update(BoRequest $request, int $id): JsonResponse
    {
        $ocorrencia = $this->ocorrenciaService->manageOcorrencia(
            RatBoDTO::fromArray($request->validated()),
            $id
        );

        if ($request->has('relatos')) {
            $this->relatoService->manageRelatos($ocorrencia, $request->input('relatos'));
        }

        $this->historicoService->log($ocorrencia, 'ocorrencia.atualizada_mobile');

        return (new RatOcorrenciaResource($ocorrencia->fresh('relatosMorph')))
            ->response()
            ->setStatusCode(200);
    }

    /**
     * PATCH /api/v1/rat/ocorrencias/{id}/finalizar
     * Finaliza uma ocorrência — não permite re-finalização.
     */
    public function finalize(int $id): JsonResponse
    {
        $ocorrencia = $this->ocorrenciaService->finalizar($id);

        $this->historicoService->log($ocorrencia, 'ocorrencia.finalizada_mobile');

        return (new RatOcorrenciaResource($ocorrencia))
            ->response()
            ->setStatusCode(200);
    }

    /**
     * DELETE /api/v1/rat/ocorrencias/{id}
     * Exclusão suave de uma ocorrência.
     */
    public function destroy(int $id): JsonResponse
    {
        $ocorrencia = RatOcorrencia::findOrFail($id);
        $ocorrencia->delete();

        return response()->json(['mensagem' => 'Ocorrência removida com sucesso.'], 200);
    }
}
