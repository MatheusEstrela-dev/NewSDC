<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Decretacoes\Requests\DesastreDataRequest;
use App\Modules\Decretacoes\Requests\StoreProcessoRequest;
use App\Modules\Decretacoes\Requests\UpdateProcessoRequest;
use App\Modules\Decretacoes\Services\DesastreDataService;
use App\Modules\Decretacoes\Services\EntradaProcessoService;
use App\Modules\Decretacoes\DTO\DesastreSubmissionDTO;
use App\Modules\Decretacoes\DTO\ProcessoRequestDTO;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controller unificado para o modulo de Decretacoes.
 *
 * FLUXO DE DADOS:
 *   Request (HTTP) -> Controller -> Service -> Model -> Banco
 *   Banco -> Model -> Resource -> Controller -> Response (JSON/Inertia)
 *
 * RESPONSABILIDADES:
 * - Receber requests HTTP (Web e API)
 * - Validar entrada via FormRequests
 * - Converter para DTOs
 * - Delegar para Services
 * - Retornar respostas formatadas
 */
class DecretacoesController extends Controller
{
    public function __construct(
        private readonly EntradaProcessoService $processoService,
        private readonly DesastreDataService $desastreService
    ) {
    }

    // =========================================================================
    // ROTAS WEB (Inertia/Vue)
    // =========================================================================

    /**
     * Lista processos com filtros.
     *
     * FLUXO: Request -> Service.list() -> Inertia (ProcessoIndex.vue)
     *
     * @param Request $request Filtros de busca (search, status, tipo_decreto)
     * @return Response Pagina Inertia com lista paginada
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
     * Exibe detalhes de um processo.
     *
     * FLUXO: ID -> Service.findById() -> Inertia (ProcessoShow.vue)
     *
     * @param int $id ID do processo
     * @return Response Pagina Inertia com detalhes
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
     * Exibe formulario de criacao de processo.
     *
     * FLUXO: Service.getFilterOptions() -> Inertia (ProcessoCreate.vue)
     *
     * @return Response Pagina Inertia com formulario vazio
     */
    public function create(): Response
    {
        $filterOptions = $this->processoService->getFilterOptions();

        return Inertia::render('Decretacoes/ProcessoCreate', [
            'filterOptions' => $filterOptions,
        ]);
    }

    /**
     * Exibe formulario de edicao de processo.
     *
     * FLUXO: ID -> Service.findById() -> Inertia (ProcessoEdit.vue)
     *
     * @param int $id ID do processo
     * @return Response Pagina Inertia com formulario preenchido
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
     * Cria novo processo.
     *
     * FLUXO: Request -> ProcessoRequestDTO -> Service.createProcesso() -> Redirect
     *
     * @param StoreProcessoRequest $request Dados validados do formulario
     * @return RedirectResponse Redireciona para pagina de detalhes
     */
    public function store(StoreProcessoRequest $request): RedirectResponse
    {
        $dto = ProcessoRequestDTO::fromRequest($request);
        $processo = $this->processoService->createProcesso($dto);

        return redirect()->route('decretacoes.processos.show', $processo->id)
            ->with('success', 'Processo cadastrado com sucesso!');
    }

    /**
     * Atualiza processo existente.
     *
     * FLUXO: Request -> ProcessoRequestDTO -> Service.updateProcesso() -> Redirect
     *
     * @param UpdateProcessoRequest $request Dados validados do formulario
     * @param int $id ID do processo
     * @return RedirectResponse Redireciona de volta com mensagem
     */
    public function update(UpdateProcessoRequest $request, int $id): RedirectResponse
    {
        $dto = ProcessoRequestDTO::fromRequest($request);
        $this->processoService->updateProcesso($dto, $id);

        return redirect()->back()->with('success', 'Processo atualizado com sucesso!');
    }

    /**
     * Remove processo.
     *
     * FLUXO: ID -> Service.delete() -> Redirect para lista
     *
     * @param int $id ID do processo
     * @return RedirectResponse Redireciona para lista
     */
    public function destroy(int $id): RedirectResponse
    {
        $this->processoService->delete($id);

        return redirect()->route('decretacoes.processos.index')
            ->with('success', 'Processo removido com sucesso!');
    }

    /**
     * Salva dados de desastres de um processo.
     *
     * FLUXO: Request -> DesastreSubmissionDTO -> DesastreDataService -> Banco
     *
     * @param DesastreDataRequest $request Dados de desastres validados
     * @param int $processoId ID do processo pai
     * @return RedirectResponse Redireciona com mensagem de sucesso/erro
     */
    public function storeDesastres(DesastreDataRequest $request, int $processoId): RedirectResponse
    {
        $processo = $this->processoService->findById($processoId);

        if (!$processo) {
            abort(404, 'Processo nao encontrado');
        }

        $dto = DesastreSubmissionDTO::fromArray($request->all());
        $result = $this->desastreService->processDesastresData($dto, $processo);

        if (!$result['success']) {
            return redirect()->back()->with('error', $result['message']);
        }

        return redirect()->back()->with('success', $result['message']);
    }

    // =========================================================================
    // ROTAS API (JSON)
    // =========================================================================

    /**
     * API: Lista processos com filtros.
     *
     * FLUXO: Request -> Service.getFilteredProcessos() -> JSON
     *
     * @param Request $request Filtros de busca
     * @return JsonResponse Lista paginada em JSON
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
     * API: Exibe detalhes de um processo.
     *
     * FLUXO: ID -> Service.findById() -> JSON
     *
     * @param int $id ID do processo
     * @return JsonResponse Dados do processo em JSON
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
     * Exporta dados normalizados para PowerBI.
     *
     * FLUXO: Request -> Service.getNormalizedDataForPowerBI() -> JSON
     *
     * DESTINO: Integracao com PowerBI para dashboards externos
     *
     * @param Request $request Filtros opcionais
     * @return JsonResponse Dados normalizados para BI
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
