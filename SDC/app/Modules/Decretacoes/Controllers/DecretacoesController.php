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
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Modules\Decretacoes\Resources\ProcessoResource;

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
     * PERFORMANCE: Usa Inertia::lazy() para carregar estatisticas sob demanda,
     * reduzindo o TTFB da carga inicial.
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

        // Enriquece processos com totais de desastres (batch - 2 queries)
        // Entrega direta via Inertia, sem necessidade de chamada API separada
        $this->processoService->enrichWithTotais($processos);

        return Inertia::render('Decretacoes/ProcessoIndex', [
            'processos' => $processos,
            'filters' => $filters,
            // Lazy: carregados apenas quando componente Vue solicitar via partial reload
            'statistics' => $this->processoService->getStatistics($filters),
            'filterOptions' => Inertia::lazy(fn () => $this->processoService->getFilterOptions()),
        ]);
    }

    /**
     * Redireciona para o index com filtro de busca pelo protocolo.
     *
     * A visualizacao de detalhes e feita via modal (DecretacaoDetailModal)
     * na pagina de listagem. Esta rota redireciona para o index com o
     * parametro de busca preenchido para abrir o processo correto.
     *
     * @param int $id ID do processo
     * @return RedirectResponse
     */
    public function show(int $id): RedirectResponse
    {
        $processo = $this->processoService->findById($id);

        if (!$processo) {
            return redirect()->route('decretacoes.index');
        }

        return redirect()->route('decretacoes.index', [
            'search' => $processo->n_protocolo_fide,
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
            'processo'      => ProcessoResource::make($processo)->resolve(),
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
            'statistics' => $this->processoService->getStatistics($request->all()),
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
    public function exportPowerBI(Request $request): StreamedResponse
    {
        $data = $this->processoService->getNormalizedDataForPowerBI($request);

        $filename = 'decretacoes_' . now()->format('Y-m-d_H-i-s') . '.csv';

        return response()->streamDownload(function () use ($data) {
            $handle = fopen('php://output', 'w');

            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            if (!empty($data)) {
                fputcsv($handle, array_keys($data[0]), ';');
            }

            foreach ($data as $row) {
                fputcsv($handle, array_values($row), ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
