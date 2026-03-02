<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Decretacoes\DTOs\ProcessoDTO;
use App\Modules\Decretacoes\Requests\StoreProcessoRequest;
use App\Modules\Decretacoes\Requests\UpdateProcessoRequest;
use App\Modules\Decretacoes\Services\DesastreService;
use App\Modules\Decretacoes\Services\ProcessoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Unified controller for Decretacoes module.
 *
 * This controller consolidates functionality from:
 * - ProcessoController (web CRUD operations)
 * - EntradaProcessoController (API operations)
 *
 * It provides both web (Inertia) and API (JSON) endpoints.
 */
class DecretacoesController extends Controller
{
    public function __construct(
        private readonly ProcessoService $processoService,
        private readonly DesastreService $desastreService
    ) {
    }

    // =========================================================================
    // WEB ROUTES (Inertia)
    // =========================================================================

    /**
     * List processos with filters.
     */
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'status', 'tipo_decreto']);
        $processos = $this->processoService->list($filters, 15);
        $statistics = $this->processoService->getStatistics();

        return Inertia::render('Decretacoes/ProcessoIndex', [
            'processos' => $processos,
            'statistics' => $statistics,
            'filters' => $filters,
        ]);
    }

    /**
     * Show processo details.
     */
    public function show(int $id): Response
    {
        $processo = $this->processoService->findById($id);

        if (!$processo) {
            abort(404, 'Processo nao encontrado');
        }

        return Inertia::render('Decretacoes/ProcessoShow', [
            'processo' => $processo,
        ]);
    }

    /**
     * Show create processo form.
     */
    public function create(): Response
    {
        $filterOptions = $this->processoService->getFilterOptions();

        return Inertia::render('Decretacoes/ProcessoCreate', [
            'filterOptions' => $filterOptions,
        ]);
    }

    /**
     * Show edit processo form.
     */
    public function edit(int $id): Response
    {
        $processo = $this->processoService->findById($id);

        if (!$processo) {
            abort(404, 'Processo nao encontrado');
        }

        $filterOptions = $this->processoService->getFilterOptions();

        return Inertia::render('Decretacoes/ProcessoEdit', [
            'processo' => $processo,
            'filterOptions' => $filterOptions,
        ]);
    }

    /**
     * Store a new processo.
     */
    public function store(StoreProcessoRequest $request): RedirectResponse
    {
        $dto = ProcessoDTO::fromRequest($request);
        $processo = $this->processoService->createProcesso($dto);

        return redirect()->route('decretacoes.processos.show', $processo->id)
            ->with('success', 'Processo cadastrado com sucesso!');
    }

    /**
     * Update an existing processo.
     */
    public function update(UpdateProcessoRequest $request, int $id): RedirectResponse
    {
        $dto = ProcessoDTO::fromRequest($request);
        $this->processoService->updateProcesso($dto, $id);

        return redirect()->back()->with('success', 'Processo atualizado com sucesso!');
    }

    /**
     * Delete a processo.
     */
    public function destroy(int $id): RedirectResponse
    {
        $this->processoService->delete($id);

        return redirect()->route('decretacoes.processos.index')
            ->with('success', 'Processo removido com sucesso!');
    }

    /**
     * Store disaster data for a processo.
     */
    public function storeDesastres(Request $request, int $processoId): RedirectResponse
    {
        $processo = $this->processoService->findById($processoId);

        if (!$processo) {
            abort(404, 'Processo nao encontrado');
        }

        $result = $this->desastreService->processDesastresData(
            $request->all(),
            $processo
        );

        if (!$result['success']) {
            return redirect()->back()->with('error', $result['message']);
        }

        return redirect()->back()->with('success', $result['message']);
    }

    // =========================================================================
    // API ROUTES (JSON)
    // =========================================================================

    /**
     * API: List processos with filters.
     */
    public function apiIndex(Request $request): JsonResponse
    {
        $data = $this->processoService->getFilteredProcessos($request);

        return response()->json([
            'success' => true,
            'data' => $data,
            'active_filters' => $this->processoService->getActiveFiltersSummary($request),
            'has_filters' => $this->processoService->hasActiveFilters($request),
            'statistics' => $this->processoService->getStatistics(),
        ]);
    }

    /**
     * API: Show processo details.
     */
    public function apiShow(int $id): JsonResponse
    {
        $processo = $this->processoService->findById($id);

        if (!$processo) {
            return response()->json([
                'success' => false,
                'message' => 'Processo nao encontrado',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $processo,
        ]);
    }

    /**
     * Export data for PowerBI.
     */
    public function exportPowerBI(Request $request): JsonResponse
    {
        $data = $this->processoService->getNormalizedDataForPowerBI($request);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}
