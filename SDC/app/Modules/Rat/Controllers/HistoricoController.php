<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Rat;

use App\Http\Controllers\Controller;
use App\Services\Rat\RatHistoricoService;
use Illuminate\Http\JsonResponse;

/**
 * Endpoint da API para a cronologia de histórico de uma ocorrência RAT.
 *
 * Usado pelo frontend (widget de cronologia) e por integrações externas.
 * Responsabilidade única: expõe histórico paginado via JSON.
 *
 * Métodos públicos: cronologia · recentes
 */
class HistoricoController extends Controller
{
    public function __construct(
        private readonly RatHistoricoService $historicoService,
    ) {}

    /**
     * GET /api/v1/rat/ocorrencias/{id}/historico
     * Cronologia paginada de eventos da ocorrência.
     */
    public function index(int $id): JsonResponse
    {
        $porPagina  = (int) request()->input('por_pagina', 20);
        $cronologia = $this->historicoService->timeline($id, $porPagina);

        return response()->json($cronologia);
    }

    /**
     * GET /api/v1/rat/ocorrencias/{id}/historico/recent
     * Últimos 10 eventos (sem paginação, para widgets/cards).
     */
    public function recent(int $id): JsonResponse
    {
        $limite  = (int) request()->input('limite', 10);
        $eventos = $this->historicoService->recent($id, $limite);

        return response()->json($eventos);
    }
}
