<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes\Services;

use App\Modules\Decretacoes\Resources\ProcessoResource;
use App\Modules\Decretacoes\Filters\ProcessoFilter;
use App\Modules\Decretacoes\Models\DecretoMunicipio;
use App\Modules\Decretacoes\Models\DesastreGrupo;
use App\Modules\Decretacoes\Models\EntradaDecreto;
use App\Modules\Decretacoes\Models\Processo;
use App\Modules\Decretacoes\Resources\ProcessosIndexResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Service responsavel por consultas complexas, filtragem e agregacao de dados de Processos.
 *
 * FLUXO DE DADOS:
 *   Filtros (Request) -> ProcessoQueryService -> Query Builder -> Banco
 *   Banco -> Models -> ProcessoResource -> JSON (API/Frontend)
 *
 * RESPONSABILIDADES:
 * - Listagem com filtros e paginacao
 * - Aplicacao de filtros dinamicos
 * - Carregamento de dados de desastre por municipio
 * - Agregacao de totais de desastres
 * - Integracao com banco SDC (pedidos de ajuda humanitaria)
 *
 * PROTECAO DE SCHEMA:
 *   Os metodos de API usam ProcessoResource para proteger contra mudancas de schema.
 *   Se uma coluna for removida, apenas o ProcessoResource precisa ser ajustado.
 */
class ProcessoQueryService
{
    /**
     * Parametros de filtro aceitos.
     */
    private const FILTER_PARAMS = [
        'search', 'data_entrada', 'data_entrada_inicio', 'data_entrada_fim',
        'processo', 'reconhecimento', 'analista', 'situacao_anormalidade',
        'data_decreto_inicio', 'data_decreto_fim', 'vigencia_status',
        'tipo_desastre_id', 'municipio_id', 'n_protocolo_fide'
    ];

    /**
     * Labels dos filtros para exibicao.
     */
    private const FILTER_LABELS = [
        'search'                => 'Busca',
        'data_entrada'          => 'Data Entrada',
        'data_entrada_inicio'   => 'Data Entrada Inicio',
        'data_entrada_fim'      => 'Data Entrada Fim',
        'processo'              => 'Tipo Processo',
        'reconhecimento'        => 'Status',
        'analista'              => 'Analista',
        'situacao_anormalidade' => 'Situacao',
        'data_decreto_inicio'   => 'Data Decreto Inicio',
        'data_decreto_fim'      => 'Data Decreto Fim',
        'vigencia_status'       => 'Vigencia',
        'tipo_desastre_id'      => 'Tipo Desastre',
        'municipio_id'          => 'Municipio',
        'n_protocolo_fide'      => 'Protocolo FIDE'
    ];

    /**
     * Cache da classificacao de desastres (COBRADE).
     */
    private ?Collection $classificacaoDesastresCache = null;

    // =========================================================================
    // LISTAGEM E FILTROS
    // =========================================================================

    /**
     * Lista processos com filtros e paginacao.
     *
     * FLUXO: Filtros -> ProcessoFilter -> Query -> Paginacao
     *
     * @param array $filters Filtros de busca
     * @param int $perPage Itens por pagina
     * @return LengthAwarePaginator Lista paginada de Processos
     */
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Processo::query();

        if (!empty($filters)) {
            $request = new Request($filters);
            $filter = new ProcessoFilter($request);
            $query = $filter->apply($query);
        }

        $query->orderBy('data_entrada', 'desc');

        return $query->paginate($perPage);
    }

    /**
     * Aplica filtros a query de Processo.
     *
     * FLUXO: Request -> ProcessoFilter -> Builder filtrado
     *
     * @param Request $request Request com parametros de filtro
     * @return Builder Query com filtros aplicados
     */
    public function applyFilters(Request $request): Builder
    {
        $query = Processo::query();

        $filter = new ProcessoFilter($request);
        $filteredQuery = $filter->apply($query);

        $filteredQuery->orderBy('data_entrada', 'desc');

        return $filteredQuery;
    }

    /**
     * Obtem opcoes de filtro disponiveis.
     *
     * DESTINO: Dropdowns de filtro no frontend
     *
     * @return array Opcoes de filtro (tipos, status, municipios, etc)
     */
    public function getFilterOptions(): array
    {
        return ProcessoFilter::getFilterOptions();
    }

    /**
     * Obtem resumo dos filtros ativos.
     *
     * DESTINO: Tags de filtro ativo no frontend
     *
     * @param Request $request Request com filtros
     * @return array Lista de filtros ativos com labels
     */
    public function getActiveFiltersSummary(Request $request): array
    {
        $activeFilters = [];
        $cobrade = $this->getClassificacaoDesastres();

        foreach (self::FILTER_LABELS as $param => $label) {
            if (!$request->filled($param)) {
                continue;
            }

            $value = $request->input($param);
            $entry = [
                'param' => $param,
                'label' => $label,
                'value' => $value,
            ];

            // Adiciona valor de exibicao para tipo de desastre
            if ($param === 'tipo_desastre_id') {
                $entry['display_value'] = $this->getDesastreDisplayValue($cobrade, (int) $value);
            }

            $activeFilters[] = $entry;
        }

        return $activeFilters;
    }

    /**
     * Verifica se ha filtros ativos.
     *
     * @param Request $request Request com filtros
     * @return bool True se ha pelo menos um filtro ativo
     */
    public function hasActiveFilters(Request $request): bool
    {
        return $request->hasAny(self::FILTER_PARAMS);
    }

    /**
     * Obtem processos filtrados e paginados.
     *
     * FLUXO: Request -> Filtros -> ProcessosIndexResource -> Array
     *
     * DESTINO: Index page (tabela principal)
     *
     * @param Request $request Request com filtros
     * @return array Dados formatados com paginacao e opcoes de filtro
     */
    public function getFilteredProcessos(Request $request): array
    {
        $query = $this->applyFilters($request);

        $data = (new ProcessosIndexResource($query))->toArray();
        $data['filter_options'] = $this->getFilterOptions();

        return $data;
    }

    // =========================================================================
    // PREPARACAO DE DADOS PARA EDICAO
    // =========================================================================

    /**
     * Prepara dados do processo para edicao.
     *
     * FLUXO: Processo -> Enriquecimento com desastre -> Processo
     *
     * @param Processo $processo Processo a preparar
     * @return Processo Processo com dados de desastre carregados
     */
    public function prepareProcessoForEdit(Processo $processo): Processo
    {
        if ($processo->tipo_desastre_id) {
            $processo->desastre = $this->getClassificacaoDesastres()
                ->firstWhere('id', $processo->tipo_desastre_id);
        }

        return $processo;
    }

    /**
     * Carrega dados de desastre para municipios do processo.
     *
     * FLUXO: Processo -> Municipios -> Categorias -> Desastres -> Campos
     *
     * DESTINO: Formulario de edicao de desastres
     *
     * @param Processo $processo Processo pai
     * @return Collection Municipios com dados de desastre carregados
     */
    public function loadMunicipiosWithDesastreData(Processo $processo): Collection
    {
        $processoId = $processo->id;

        return $processo->municipios->transform(function ($municipio) use ($processoId) {
            $municipioId = $municipio->id;

            // Carrega protocolo FIDE do municipio
            $decretoMunicipio = DecretoMunicipio::where('entrada_processos_id', $processoId)
                ->where('municipio_id', $municipioId)
                ->first();

            $municipio->n_protocolo_fide = $decretoMunicipio?->n_protocolo_fide;
            $municipio->updated_at = $decretoMunicipio?->updated_at;

            // Carrega hierarquia completa de desastres
            $municipio->categorias = DesastreGrupo::with(['desastres' => function ($query) use ($processoId, $municipioId) {
                $query->with(['items' => function ($query) use ($processoId, $municipioId) {
                    $query->with(['campos' => function ($query) use ($processoId, $municipioId) {
                        $query->leftjoin('dec_entrada_desastres', function ($join) use ($processoId, $municipioId) {
                            $join->on('dec_desastre_item_campos.id', '=', 'dec_entrada_desastres.item_campo_id')
                                ->where('dec_entrada_desastres.entrada_processo_id', $processoId)
                                ->where('dec_entrada_desastres.municipio_id', $municipioId)
                                ->whereNull('dec_entrada_desastres.deleted_at');
                        })
                        ->select('dec_desastre_item_campos.*', 'dec_entrada_desastres.valor', 'dec_entrada_desastres.id as entrada_desastre_id');
                    }]);
                }])
                ->leftjoin('dec_entrada_categoria_desastres', function ($join) use ($processoId, $municipioId) {
                    $join->on('dec_desastre_categorias.id', '=', 'dec_entrada_categoria_desastres.categoria_id')
                        ->where('dec_entrada_categoria_desastres.entrada_processo_id', $processoId)
                        ->where('dec_entrada_categoria_desastres.municipio_id', $municipioId);
                })
                ->select('dec_desastre_categorias.*', 'dec_entrada_categoria_desastres.descricao', 'dec_entrada_categoria_desastres.id as entrada_categoria_desastre_id');
            }])->get();

            return $municipio;
        });
    }

    /**
     * Carrega informacoes de decreto de um processo.
     *
     * FLUXO: ID -> EntradaDecreto -> Array formatado
     *
     * @param int $processoId ID do processo
     * @return array Informacoes de decreto
     */
    public function loadInformacoesDecreto(int $processoId): array
    {
        $entradaDecretos = EntradaDecreto::where('entrada_processos_id', $processoId)->get();
        $informacoesDecreto = [];

        foreach ($entradaDecretos as $entradaDecreto) {
            $informacoesDecreto[] = [
                'id' => $entradaDecreto->decreto_categoria_id,
                'tipo' => '',
                'observacao' => $entradaDecreto->observacao ?? ''
            ];
        }

        return $informacoesDecreto;
    }

    // =========================================================================
    // AGREGACAO DE TOTAIS DE DESASTRES
    // =========================================================================

    /**
     * Calcula totais de desastres a partir de collection de entradas.
     *
     * FLUXO: Collection de Processos -> Join multiplo -> Agregacao -> Totais
     *
     * DESTINO: Dashboard, relatorios
     *
     * @param Collection $entradas Collection de processos
     * @return Collection Collection enriquecida com totais
     */
    public function getTotalDesastresCountFromEntradas(Collection $entradas): Collection
    {
        $processoIds = $entradas->pluck('id');

        if ($processoIds->isEmpty()) {
            return $entradas;
        }

        // Query de agregacao com joins
        $allTotals = DB::table('dec_entrada_categoria_desastres as ecd')
            ->join('dec_entrada_desastres as ed', 'ecd.id', '=', 'ed.entrada_categoria_desastre_id')
            ->join('dec_desastre_item_campos as dic', 'ed.item_campo_id', '=', 'dic.id')
            ->join('dec_desastre_categorias as dc', 'ecd.categoria_id', '=', 'dc.id')
            ->join('cedec_municipio as m', 'ed.municipio_id', '=', 'm.id')
            ->whereIn('ecd.entrada_processo_id', $processoIds)
            ->whereIn('dic.tipo', ['number', 'currency'])
            ->select(
                'ecd.entrada_processo_id',
                'm.p_nome as municipio',
                'dc.titulo as categoria_titulo',
                'dic.titulo as desastre_campo_titulo',
                'dic.tipo',
                DB::raw('SUM(ed.valor) as total_valor')
            )
            ->groupBy('ecd.entrada_processo_id', 'm.p_nome', 'dc.titulo', 'dic.titulo', 'dic.tipo')
            ->get();

        // Agrupa por municipio
        $groupedTotalsPorMunicipio = $allTotals->groupBy('entrada_processo_id')->map(function ($processoItems) {
            return $processoItems->groupBy('municipio')->map(function ($municipioItems) {
                return $municipioItems->groupBy('categoria_titulo')->map(function ($categoriaItems) {
                    return $categoriaItems->keyBy('desastre_campo_titulo')->map(function ($item) {
                        return $item->tipo === 'currency' ? number_format((float) $item->total_valor, 2, ',', '.') : $item->total_valor;
                    });
                });
            });
        });

        // Agrupa totais gerais
        $groupedTotals = $allTotals->groupBy('entrada_processo_id')->map(function ($processoItems) {
            return $processoItems->groupBy('categoria_titulo')->map(function ($categoriaItems) {
                return $categoriaItems->groupBy('desastre_campo_titulo')->map(function ($items) {
                    $total = $items->sum('total_valor');
                    $tipo = $items->first()->tipo;
                    return $tipo === 'currency' ? number_format((float) $total, 2, ',', '.') : $total;
                });
            });
        });

        // Adiciona totais a cada entrada
        $entradas->each(function ($entrada) use ($groupedTotals, $groupedTotalsPorMunicipio) {
            $entrada->desastre_totals = $groupedTotals->get($entrada->id, collect());
            $entrada->desastre_totals_por_municipio = $groupedTotalsPorMunicipio->get($entrada->id, collect());
        });

        return $entradas;
    }

    // =========================================================================
    // INTEGRACAO COM BANCO SDC (AJUDA HUMANITARIA)
    // =========================================================================

    /**
     * Busca dados de pedidos de ajuda humanitaria do banco SDC.
     *
     * FLUXO: Numero Decreto -> Conexao SDC -> Pedidos agrupados
     *
     * DESTINO: Aba de ajuda humanitaria no show de processo
     *
     * @param string|null $numeroDecreto Numero do decreto
     * @return Collection Pedidos agrupados por codigo
     */
    public function getPedidoAhData(?string $numeroDecreto): Collection
    {
        if (empty($numeroDecreto)) {
            return collect();
        }

        $tpItemCase = "CASE WHEN LOWER(aju_h_pedido_pedid.tramit) = 'atendido' THEN 'RECEBIDO' ELSE aju_h_pedido_itens.tp_item END";

        $registros = DB::connection('sdc')->table('aju_h_pedido_pedid')
            ->join('aju_h_pedido_itens', 'aju_h_pedido_pedid.id', '=', 'aju_h_pedido_itens.id_pedido')
            ->select(
                'aju_h_pedido_itens.codigo',
                'aju_h_pedido_pedid.tramit as status',
                'aju_h_pedido_itens.descricao_item',
                DB::raw("$tpItemCase AS tp_item"),
                DB::raw('SUM(aju_h_pedido_itens.qtd) AS total_qtd')
            )
            ->where('aju_h_pedido_pedid.num_decreto', $numeroDecreto)
            ->groupBy('aju_h_pedido_itens.codigo', 'aju_h_pedido_itens.descricao_item', DB::raw($tpItemCase), 'aju_h_pedido_pedid.tramit')
            ->orderBy('aju_h_pedido_itens.descricao_item')
            ->get();

        return $registros->groupBy('codigo')->map(function ($items) {
            return $items->map(fn ($item) => [
                'codigo'         => $item->codigo,
                'status'         => $item->status,
                'descricao_item' => $item->descricao_item,
                'tp_item'        => $item->tp_item,
                'total_qtd'      => $item->total_qtd,
            ]);
        });
    }

    // =========================================================================
    // HELPERS PRIVADOS
    // =========================================================================

    /**
     * Obtem classificacao de desastres com cache.
     *
     * @return Collection Lista de desastres (COBRADE)
     */
    private function getClassificacaoDesastres(): Collection
    {
        if ($this->classificacaoDesastresCache === null) {
            $this->classificacaoDesastresCache = collect(include app_path('Enums/classificacao_desastres.php'))
                ->sortBy('a_definicao')
                ->values();
        }

        return $this->classificacaoDesastresCache;
    }

    /**
     * Obtem valor de exibicao para tipo de desastre.
     *
     * @param Collection $cobrade Lista COBRADE
     * @param int $tipoDesastreId ID do tipo
     * @return string|null "COBRADE - Definicao" ou null
     */
    private function getDesastreDisplayValue(Collection $cobrade, int $tipoDesastreId): ?string
    {
        $match = $cobrade->firstWhere('id', $tipoDesastreId);

        if (!$match) {
            return null;
        }

        $labelParts = array_filter([
            $match['cobrade'] ?? null,
            $match['a_definicao'] ?? $match['subtipo'] ?? $match['tipo'] ?? $match['subgrupo'] ?? $match['grupo'] ?? null,
        ]);

        return implode(' - ', $labelParts) ?: null;
    }

    // =========================================================================
    // METODOS API - Usam ProcessoResource para proteger contra mudancas de schema
    // Se uma coluna for removida, apenas o ProcessoResource precisa ser ajustado
    // =========================================================================

    /**
     * Lista processos formatados para API usando Resource.
     *
     * FLUXO: Filtros -> list() -> ProcessoResource::collection -> JSON
     *
     * DESTINO: API REST
     *
     * @param array $filters Filtros de busca
     * @param int $perPage Itens por pagina
     * @return array Dados com meta de paginacao
     */
    public function listForApi(array $filters = [], int $perPage = 15): array
    {
        $paginator = $this->list($filters, $perPage);

        return [
            'data' => ProcessoResource::collection($paginator)->resolve(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ];
    }

    /**
     * Lista processos com formato reduzido para listagens.
     *
     * FLUXO: Filtros -> list() -> ProcessoResource::toListArray -> JSON
     *
     * DESTINO: API REST (listagens compactas)
     *
     * @param array $filters Filtros de busca
     * @param int $perPage Itens por pagina
     * @return array Dados compactos com meta de paginacao
     */
    public function listForApiCompact(array $filters = [], int $perPage = 15): array
    {
        $paginator = $this->list($filters, $perPage);

        return [
            'data' => collect($paginator->items())->map(
                fn(Processo $processo) => (new ProcessoResource($processo))->toListArray()
            )->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ];
    }

    /**
     * Retorna detalhes completos de um processo para API.
     *
     * FLUXO: ID -> Processo (com relacionamentos) -> ProcessoResource -> JSON
     *
     * DESTINO: API REST (show)
     *
     * @param int $id ID do processo
     * @return array|null Dados completos ou null se nao encontrado
     */
    public function showForApi(int $id): ?array
    {
        $processo = Processo::with(['municipios', 'desastres'])->find($id);
        return $processo ? ProcessoResource::make($processo)->resolve() : null;
    }

    /**
     * Busca processos vigentes usando Resource.
     *
     * FLUXO: Query -> Filter (isVigente) -> Collection
     *
     * @return Collection Processos vigentes
     */
    public function getVigentes(): Collection
    {
        $processos = Processo::whereNotNull('data_publicacao_mg')
            ->whereNotNull('prazo_vigencia')
            ->get();

        return $processos->filter(function (Processo $processo) {
            $resource = new ProcessoResource($processo);
            return $resource->isVigente();
        });
    }

    /**
     * Busca processos vigentes formatados para API.
     *
     * FLUXO: getVigentes() -> ProcessoResource::collection -> JSON
     *
     * DESTINO: API REST / Dashboard
     *
     * @return array Processos vigentes formatados
     */
    public function getVigentesForApi(): array
    {
        return ProcessoResource::collection($this->getVigentes())->resolve();
    }

    /**
     * Busca processos proximos de vencer (30 dias).
     *
     * FLUXO: Query -> Filter (isProximoVencer) -> Collection
     *
     * @return Collection Processos proximos de vencer
     */
    public function getProximosVencer(): Collection
    {
        $processos = Processo::whereNotNull('data_publicacao_mg')
            ->whereNotNull('prazo_vigencia')
            ->get();

        return $processos->filter(function (Processo $processo) {
            $resource = new ProcessoResource($processo);
            return $resource->isProximoVencer();
        });
    }

    /**
     * Busca processos proximos de vencer formatados para API.
     *
     * FLUXO: getProximosVencer() -> ProcessoResource::collection -> JSON
     *
     * DESTINO: API REST / Dashboard (alertas)
     *
     * @return array Processos proximos de vencer formatados
     */
    public function getProximosVencerForApi(): array
    {
        return ProcessoResource::collection($this->getProximosVencer())->resolve();
    }
}
