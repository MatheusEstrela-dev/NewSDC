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
        $filters = $request->only([
            'search',
            'data_entrada',
            'data_entrada_inicio',
            'data_entrada_fim',
            'data_inicio',
            'data_fim',
            'processo',
            'reconhecimento',
            'analista',
            'situacao_anormalidade',
            'data_decreto_inicio',
            'data_decreto_fim',
            'vigencia_status',
            'tipo_desastre_id',
            'municipio_id',
            'n_protocolo_fide',
        ]);
        $processos = $this->processoService->list($filters, 15);
        $statistics = $this->processoService->getStatistics();
        $filterOptions = $this->processoService->getFilterOptions();

        return Inertia::render('Decretacoes/ProcessoIndex', [
            'processos' => $processos,
            'statistics' => $statistics,
            'filters' => $filters,
            'filterOptions' => $filterOptions,
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

        // getFilterOptions() retorna: analistas, reconhecimentos, municipios, tipos_desastre, status_options
        // O ProcessoCreate.vue espera props separadas no top-level
        return Inertia::render('Decretacoes/ProcessoCreate', [
            'tiposDesastre' => $filterOptions['tipos_desastre'] ?? [],
            'cobrades'      => $filterOptions['tipos_desastre'] ?? [], // cobrade vem dos tipos de desastre
            'municipios'    => $filterOptions['municipios'] ?? [],
            'redecs'        => $filterOptions['redecs'] ?? [], 
            'statusOptions' => $filterOptions['status_options'] ?? [],
            'analistas'     => $filterOptions['analistas'] ?? [],
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
            'processo'      => $processo,
            'tiposDesastre' => $filterOptions['tipos_desastre'] ?? [],
            'cobrades'      => $filterOptions['tipos_desastre'] ?? [],
            'municipios'    => $filterOptions['municipios'] ?? [],
            'redecs'        => $filterOptions['redecs'] ?? [],
            'statusOptions' => $filterOptions['status_options'] ?? [],
            'analistas'     => $filterOptions['analistas'] ?? [],
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

        return redirect()->route('decretacoes.show', $processo->id)
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
     * Remove processo (soft delete).
     *
     * FLUXO: ID -> Service.delete() -> Redirect para lista ou erro
     *
     * @param int $id ID do processo
     * @return RedirectResponse Redireciona para lista ou volta com erro
     */
    public function destroy(int $id): RedirectResponse
    {
        $result = $this->processoService->delete($id);

        if (!$result['success']) {
            return redirect()->back()->with('error', $result['message']);
        }

        return redirect()->route('decretacoes.index')
            ->with('success', $result['message']);
    }

    /**
     * Exibe formulario de edicao de dados de desastres.
     *
     * FLUXO: ID -> Service.findById() -> Service.loadMunicipiosWithDesastreData() -> Inertia
     *
     * @param int $id ID do processo
     * @return Response Pagina Inertia com formulario de desastres preenchido
     */
    public function editDesastres(int $id): Response
    {
        $processo = $this->processoService->findById($id);

        if (!$processo) {
            abort(404, 'Processo nao encontrado');
        }

        $municipiosWithDesastres = $this->processoService->loadMunicipiosWithDesastreData($processo);
        $filterOptions = $this->processoService->getFilterOptions();

        return Inertia::render('Decretacoes/ProcessoDesastresEdit', [
            'processo' => $processo,
            'municipios' => $municipiosWithDesastres,
            'filterOptions' => $filterOptions,
        ]);
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
