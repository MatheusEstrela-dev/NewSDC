<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Rat\RatAuditService;
use Illuminate\Http\JsonResponse;

/**
 * Endpoints da API para a trilha de auditoria do módulo RAT.
 *
 * Métodos públicos: index · show
 */
class RatAuditController extends Controller
{
    public function __construct(
        private readonly RatAuditService $auditService
    ) {}

    /**
     * GET /api/rat-audit
     * Lista de logs de auditoria globais do módulo RAT paginados.
     */
    public function index(): JsonResponse
    {
        $history = $this->auditService->history(
            'rat_ocorrencias',
            0,
            request()->integer('per_page', 20)
        );

        return response()->json($history);
    }

    /**
     * GET /api/rat-audit/{id}
     * Histórico de auditoria de uma ocorrência específica.
     */
    public function show(int $id): JsonResponse
    {
        $history = $this->auditService->history(
            'rat_ocorrencias',
            $id,
            request()->integer('per_page', 20)
        );

        return response()->json($history);
    }
}
