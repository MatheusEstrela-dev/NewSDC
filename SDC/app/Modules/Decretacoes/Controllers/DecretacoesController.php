<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Decretacoes\Requests\DesastreDataRequest;
use App\Modules\Decretacoes\Requests\StoreProcessoRequest;
use App\Modules\Decretacoes\Requests\UpdateProcessoRequest;
use App\Modules\Decretacoes\Services\DesastreDataService;
use App\Modules\Decretacoes\Services\EntradaProcessoService;
use App\Modules\Decretacoes\Services\ProcessoExportRedecService;
use App\Modules\Decretacoes\DTO\DesastreSubmissionDTO;
use App\Modules\Decretacoes\DTO\ProcessoRequestDTO;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            'redec_id',
            'n_protocolo_fide',
            // Atalho dos cards de estatistica. Sem ele na whitelist o filtro
            // chegava a aparecer como chip na interface e era descartado aqui,
            // antes do ProcessoFilter, e a listagem nao mudava.
            'tipo_lancamento',
            // Ordenacao das colunas. O valor e validado contra a whitelist de
            // ProcessoQueryService antes de chegar ao ORDER BY.
            'sort',
            'direction',
        ]);
        $processos = $this->processoService->list($filters, 15);

        // Batch 1: enriquece totais de desastres (2 queries)
        $this->processoService->enrichWithTotais($processos);

        $processoIds = $processos->getCollection()->pluck('id')->toArray();

        // Batch 2: municipios de todas as 3 fontes em 5 queries total
        $municipiosPorProcesso = $this->loadMunicipiosBatch($processoIds);

        // Batch 3: pedidos de ajuda humanitaria por decreto_municipal (1 query)
        $decretos = $processos->getCollection()->pluck('decreto_municipal')->filter()->unique()->values()->toArray();
        $pedidosAhPorDecreto = $this->loadPedidosAhBatch($decretos);

        // Enum classificacao de desastres (in-memory, sem query)
        $cobrade = collect(include app_path('Enums/classificacao_desastres.php'));

        // Transforma em array completo via ProcessoResource (dados suficientes para o modal)
        $processos->getCollection()->transform(function ($processo) use ($municipiosPorProcesso, $pedidosAhPorDecreto, $cobrade) {
            // Injeta dados pre-carregados como atributos para evitar N+1
            $processo->setAttribute('_municipios', $municipiosPorProcesso[$processo->id] ?? []);
            $processo->setAttribute('pedidos_ah', $pedidosAhPorDecreto[$processo->decreto_municipal] ?? collect());

            $match = $cobrade->firstWhere('id', $processo->tipo_desastre_id);
            if ($match) {
                $processo->setAttribute('tipo_desastre_completo', [
                    'id'        => $match['id'] ?? null,
                    'cobrade'   => $match['cobrade'] ?? null,
                    'categoria' => $match['categoria'] ?? null,
                    'grupo'     => $match['grupo'] ?? null,
                    'subgrupo'  => $match['subgrupo'] ?? null,
                    'tipo'      => $match['tipo'] ?? null,
                    'subtipo'   => $match['subtipo'] ?? null,
                    'nome'      => $match['a_definicao'] ?? $match['subtipo'] ?? $match['tipo'] ?? null,
                    'definicao' => $match['a_definicao'] ?? null,
                ]);
            }

            return ProcessoResource::make($processo)->resolve();
        });

        return Inertia::render('Decretacoes/ProcessoIndex', [
            'processos' => $processos,
            'filters' => $filters,
            // Lazy: carregados apenas quando componente Vue solicitar via partial reload
            'statistics' => $this->processoService->getStatistics($filters),
            'filterOptions' => Inertia::lazy(fn () => $this->processoService->getFilterOptions()),
        ]);
    }

    /**
     * Carrega municipios de uma lista de processos em batch.
     *
     * ESTRATEGIA DE RESOLUCAO (em ordem):
     * 1. dec_decreto_municipios: extrai codigo IBGE do n_protocolo_fide (MG-F-{ibge}-...)
     *    e busca em municipios.codigo_ibge
     * 2. Fallback por municipio_id direto em municipios.id (registros novos)
     *
     * @param array $processoIds IDs dos processos
     * @return array<int, array> Map de processo_id => array de municipios
     */
    private function loadMunicipiosBatch(array $processoIds): array
    {
        if (empty($processoIds)) {
            return [];
        }

        // Fonte principal: dec_decreto_municipios com n_protocolo_fide (tem o IBGE embutido)
        $decRows = DB::table('dec_decreto_municipios')
            ->whereIn('entrada_processos_id', $processoIds)
            ->whereNull('deleted_at')
            ->select('entrada_processos_id as processo_id', 'municipio_id', 'n_protocolo_fide')
            ->get();

        if ($decRows->isEmpty()) {
            return [];
        }

        // Extrai codigos IBGE do n_protocolo_fide (formato MG-F-{7-digitos}-...)
        $ibgeCodes    = [];
        $directIds    = [];
        $rowMap       = []; // municipio_id => ['ibge' => ..., 'direct_id' => ...]

        foreach ($decRows as $row) {
            $mid   = (int) $row->municipio_id;
            $parts = explode('-', (string) $row->n_protocolo_fide);
            $ibge  = (isset($parts[2]) && strlen($parts[2]) === 7) ? $parts[2] : null;

            if ($ibge) {
                $ibgeCodes[$ibge]  = true;
                $rowMap[$mid]      = ['ibge' => $ibge];
            } else {
                $directIds[$mid]   = true;
                $rowMap[$mid]      = ['direct_id' => $mid];
            }
        }

        // Resolve por IBGE (registros legados com n_protocolo_fide valido)
        $byIbge = !empty($ibgeCodes)
            ? DB::table('municipios')
                ->whereIn('codigo_ibge', array_keys($ibgeCodes))
                ->select('id', 'nome', 'codigo_ibge')
                ->get()
                ->keyBy('codigo_ibge')
            : collect();

        // Resolve por ID direto (registros novos sem IBGE no protocolo)
        $byId = !empty($directIds)
            ? DB::table('municipios')
                ->whereIn('id', array_keys($directIds))
                ->select('id', 'nome', 'codigo_ibge')
                ->get()
                ->keyBy('id')
            : collect();

        // Monta mapa: municipio_id => dados do municipio
        $municipioMap = [];
        foreach ($rowMap as $mid => $meta) {
            if (isset($meta['ibge']) && isset($byIbge[$meta['ibge']])) {
                $m = $byIbge[$meta['ibge']];
                $municipioMap[$mid] = ['id' => $m->id, 'nome' => $m->nome, 'codigo_ibge' => $m->codigo_ibge ?? null];
            } elseif (isset($meta['direct_id']) && isset($byId[$meta['direct_id']])) {
                $m = $byId[$meta['direct_id']];
                $municipioMap[$mid] = ['id' => $m->id, 'nome' => $m->nome, 'codigo_ibge' => $m->codigo_ibge ?? null];
            } else {
                // Fallback para processos TEMPORARIO: municipio_id CEDEC sem correspondencia na tabela municipios
                $municipioMap[$mid] = ['id' => $mid, 'nome' => "Municipio $mid", 'codigo_ibge' => null];
            }
        }

        // Agrupa por processo_id, eliminando duplicatas
        $result = [];
        foreach ($decRows as $par) {
            $pid = (int) $par->processo_id;
            $mid = (int) $par->municipio_id;
            $mun = $municipioMap[$mid];
            $result[$pid][$mid] = $mun;
        }

        // Ordena municipios por nome em cada processo
        foreach ($result as &$muns) {
            usort($muns, fn($a, $b) => strcmp($a['nome'], $b['nome']));
            $muns = array_values($muns);
        }

        return $result;
    }

    /**
     * Carrega pedidos de ajuda humanitaria para uma lista de decretos em batch (1 query).
     *
     * @param array $decretos Numeros de decreto (decreto_municipal)
     * @return array<string, mixed> Map de decreto => pedidos agrupados por codigo
     */
    private function loadPedidosAhBatch(array $decretos): array
    {
        if (empty($decretos)) {
            return [];
        }

        try {
            $tpItemCase = "CASE WHEN LOWER(aju_h_pedido_pedid.tramit) = 'atendido' THEN 'RECEBIDO' ELSE aju_h_pedido_itens.tp_item END";

            $registros = DB::table('aju_h_pedido_pedid')
                ->join('aju_h_pedido_itens', 'aju_h_pedido_pedid.id', '=', 'aju_h_pedido_itens.id_pedido')
                ->select(
                    'aju_h_pedido_pedid.num_decreto',
                    'aju_h_pedido_itens.codigo',
                    'aju_h_pedido_pedid.tramit as status',
                    'aju_h_pedido_itens.descricao_item',
                    DB::raw("$tpItemCase AS tp_item"),
                    DB::raw('SUM(aju_h_pedido_itens.qtd) AS total_qtd')
                )
                ->whereIn('aju_h_pedido_pedid.num_decreto', $decretos)
                ->groupBy(
                    'aju_h_pedido_pedid.num_decreto',
                    'aju_h_pedido_itens.codigo',
                    'aju_h_pedido_itens.descricao_item',
                    DB::raw($tpItemCase),
                    'aju_h_pedido_pedid.tramit'
                )
                ->orderBy('aju_h_pedido_itens.descricao_item')
                ->get();

            $result = [];
            foreach ($registros as $row) {
                $decreto = $row->num_decreto;
                $codigo  = $row->codigo;
                if (!isset($result[$decreto][$codigo])) {
                    $result[$decreto][$codigo] = [];
                }
                $result[$decreto][$codigo][] = [
                    'codigo'         => $codigo,
                    'status'         => $row->status,
                    'descricao_item' => $row->descricao_item,
                    'tp_item'        => $row->tp_item,
                    'total_qtd'      => $row->total_qtd,
                ];
            }

            return $result;
        } catch (\Throwable $e) {
            // Conexao sdc pode nao estar disponivel neste ambiente (silencia graciosamente)
            return [];
        }
    }

    /**
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
    public function create(Request $request): Response
    {
        $filterOptions = $this->processoService->getFilterOptions();

        // Wizard de criacao em duas abas:
        //   - GET /decretacoes/create               -> Aba 1 (form vazio), Aba 2 disabled
        //   - GET /decretacoes/create?id={newId}    -> apos store(): Aba 1 hidratada,
        //                                              Aba 2 habilitada com a arvore de desastres.
        // Importante: NUNCA redireciona para /edit - o fluxo de CRIACAO permanece em /create.
        $processo = null;
        $municipiosDesastres = [];

        $id = $request->query('id');
        if ($id) {
            $found = $this->processoService->findById((int) $id);
            if ($found) {
                $municipiosDesastres = $this->processoService->loadMunicipiosWithDesastreData($found);
                $processo = ProcessoResource::make($found)->resolve();
            }
        }

        return Inertia::render('Decretacoes/ProcessoCreate', [
            'tiposDesastre'        => $filterOptions['tipos_desastre'] ?? [],
            // Lista numerada pelo codigo COBRADE (padrao nacional)
            'cobrades'             => $filterOptions['cobrades'] ?? [],
            'municipios'           => $filterOptions['municipios'] ?? [],
            'redecs'               => $filterOptions['redecs'] ?? [],
            'statusOptions'        => $filterOptions['status_options'] ?? [],
            'analistas'            => $filterOptions['analistas'] ?? [],
            'processo'             => $processo,
            'municipiosDesastres'  => $municipiosDesastres,
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
            // Lista numerada pelo codigo COBRADE (padrao nacional)
            'cobrades'      => $filterOptions['cobrades'] ?? [],
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

        // Mantem o usuario no fluxo de CRIACAO (/create), nao mistura com /edit.
        // O parametro ?id= avisa o create() a hidratar a Aba 2 do wizard.
<<<<<<< Updated upstream
        //
        // SEM flash de sucesso: o flash e pintado pelo FlashNotification, que
        // fica no canto inferior. A pagina de criacao ja avisa pelo toast do
        // canto superior (ProcessoCreate.handleSubmit), e as duas mensagens
        // apareciam juntas dizendo a mesma coisa.
=======
        // Sem flash de sucesso: o feedback e exibido pelo toast da propria pagina
        // (evita notificacao duplicada no create/edit).
>>>>>>> Stashed changes
        return redirect()->route('decretacoes.create', ['id' => $processo->id]);
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

        // Sem flash de sucesso: o toast da pagina ja informa a atualizacao.
        return redirect()->back();
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

        // Sem flash de sucesso: o toast da pagina ja informa o salvamento.
        return redirect()->back();
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

        return $this->streamCsv($data, 'decretacoes_' . now()->format('Y-m-d_H-i-s') . '.csv');
    }

    /**
     * Exporta as decretacoes agrupadas por REDEC.
     *
     * FLUXO: Request -> ProcessoExportRedecService -> CSV
     *
     * DESTINO: botao "Exportar por REDEC" da listagem
     *
     * Respeita os filtros da tela. Sem `redec_id` no request, sai a serie
     * completa ordenada por REDEC; com `redec_id`, apenas aquela REDEC (o
     * recorte e feito pelo mesmo ProcessoFilter da listagem, entao o CSV e o que
     * a tela mostraria).
     *
     * @param Request $request Filtros da listagem + redec_id opcional
     */
    public function exportRedec(Request $request, ProcessoExportRedecService $exportRedecService): StreamedResponse
    {
        $linhas = $exportRedecService->getLinhasPorRedec($request);

        $sufixo = $request->filled('redec_id')
            ? 'redec_' . (int) $request->input('redec_id')
            : 'todas_redecs';

        return $this->streamCsv(
            $linhas,
            "decretacoes_{$sufixo}_" . now()->format('Y-m-d_H-i-s') . '.csv',
            $this->cabecalhoExportRedec()
        );
    }

    /**
     * Cabecalho fixo do CSV por REDEC.
     *
     * Fixo (e nao derivado da primeira linha) para que a planilha tenha sempre as
     * mesmas colunas, inclusive quando o recorte nao devolve nenhuma linha.
     *
     * @return array<int, string>
     */
    private function cabecalhoExportRedec(): array
    {
        return [
            'redec_id', 'redec', 'redec_regiao', 'uf', 'municipio', 'codigo_ibge',
            'processo_id', 'protocolo', 'protocolo_municipio', 'tipo_processo',
            'data_entrada', 'data_ocorrencia', 'cobrade', 'tipo_desastre',
            'situacao_anormalidade', 'status', 'decreto_municipal',
            'data_decreto_municipal', 'data_publicacao_mg', 'prazo_vigencia_dias',
            'data_vencimento', 'dias_restantes', 'situacao_vigencia', 'analista',
        ];
    }

    /**
     * Envia um array de linhas como CSV (separador `;`, BOM UTF-8 para o Excel).
     *
     * @param array<int, array<string, mixed>> $linhas
     * @param array<int, string>|null $cabecalho Colunas; sem ele usa as chaves da primeira linha
     */
    private function streamCsv(array $linhas, string $filename, ?array $cabecalho = null): StreamedResponse
    {
        return response()->streamDownload(function () use ($linhas, $cabecalho) {
            $handle = fopen('php://output', 'w');

            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            $colunas = $cabecalho ?? (!empty($linhas) ? array_keys($linhas[0]) : []);

            if (!empty($colunas)) {
                fputcsv($handle, $colunas, ';');
            }

            foreach ($linhas as $linha) {
                // Ordena os valores pelo cabecalho: garante que cada coluna
                // receba o seu valor mesmo se uma linha vier com outra ordem.
                $valores = $cabecalho === null
                    ? array_values($linha)
                    : array_map(fn (string $coluna) => $linha[$coluna] ?? null, $cabecalho);

                fputcsv($handle, $valores, ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
