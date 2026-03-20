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
use Illuminate\Support\Str;

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
            ->leftJoin('municipios as m', 'ed.municipio_id', '=', 'm.id')
            ->whereIn('ecd.entrada_processo_id', $processoIds)
            ->whereIn('dic.tipo', ['number', 'currency'])
            ->select(
                'ecd.entrada_processo_id',
                'm.nome as municipio',
                'dc.titulo as categoria_titulo',
                'dic.titulo as desastre_campo_titulo',
                'dic.tipo',
                DB::raw('SUM(ed.valor) as total_valor')
            )
            ->groupBy('ecd.entrada_processo_id', 'm.nome', 'dc.titulo', 'dic.titulo', 'dic.tipo')
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

        if (!$processo) {
            return null;
        }

        $totais = $this->calculateTotaisForProcesso($id);
        $pedidosAh = $this->getPedidoAhData($processo->decreto_municipal);
        $tipoDesastre = $this->getTipoDesastreCompleto($processo->tipo_desastre_id);

        $processo->setAttribute('totais', $totais);
        $processo->setAttribute('pedidos_ah', $pedidosAh);
        $processo->setAttribute('tipo_desastre_completo', $tipoDesastre);

        return ProcessoResource::make($processo)->resolve();
    }

    // Constantes para categorias de desastre (mesmo padrao do ProcessoExportService)
    private const CAT_DANOS_HUMANOS_ID = 1;
    private const CAT_DANOS_MATERIAIS = 'DANOS MATERIAIS';
    private const CAT_PREJUIZOS_PUBLICOS = 'PREJUÍZOS ECONÔMICOS PÚBLICOS';
    private const CAT_PREJUIZOS_PRIVADOS = 'PREJUÍZOS ECONÔMICOS PRIVADOS';

    private const DANOS_HUMANOS_MAP = [
        1 => 'obitos',
        2 => 'feridos',
        3 => 'feridos',
        4 => 'desabrigados',
        5 => 'desalojados',
        6 => 'desaparecidos',
        7 => 'outros_afetados',
    ];

    private const DANOS_HUMANOS_TITLES = [
        'obito' => 'obitos',
        'morto' => 'obitos',
        'ferido' => 'feridos',
        'enfermo' => 'feridos',
        'desabrigado' => 'desabrigados',
        'desalojado' => 'desalojados',
        'desaparecido' => 'desaparecidos',
        'outros' => 'outros_afetados',
        'afetados' => 'outros_afetados',
    ];

    /**
     * Enriquece processos do paginador com totais de desastres (batch).
     * Usa apenas 2 queries no total, independente do numero de processos.
     *
     * DESTINO: Controller index (Inertia) - entrega direta sem API
     *
     * @param \Illuminate\Pagination\LengthAwarePaginator $paginator Paginador com processos
     */
    public function enrichPaginatorWithTotais(\Illuminate\Pagination\LengthAwarePaginator $paginator): void
    {
        $processos = collect($paginator->items());
        $processoIds = $processos->pluck('id');

        if ($processoIds->isEmpty()) {
            return;
        }

        // Query batch: totais (materiais, prejuizos) - 1 query para todos os processos
        $allTotals = DB::table('dec_entrada_categoria_desastres as ecd')
            ->join('dec_entrada_desastres as ed', 'ecd.id', '=', 'ed.entrada_categoria_desastre_id')
            ->join('dec_desastre_item_campos as dic', 'ed.item_campo_id', '=', 'dic.id')
            ->join('dec_desastre_categorias as dc', 'ecd.categoria_id', '=', 'dc.id')
            ->leftJoin('municipios as m', 'ed.municipio_id', '=', 'm.id')
            ->whereIn('ecd.entrada_processo_id', $processoIds)
            ->whereIn('dic.tipo', ['number', 'currency'])
            ->select(
                'ecd.entrada_processo_id',
                'ed.municipio_id',
                'm.nome as municipio_nome',
                'dc.titulo as categoria_titulo',
                'dic.titulo as desastre_campo_titulo',
                'dic.tipo',
                DB::raw('SUM(ed.valor) as total_valor')
            )
            ->groupBy('ecd.entrada_processo_id', 'ed.municipio_id', 'm.nome', 'dc.titulo', 'dic.titulo', 'dic.tipo')
            ->get();

        // Query batch: danos humanos - 1 query para todos os processos
        $danosHumanosBatch = DB::table('dec_entrada_desastres as ed')
            ->join('dec_entrada_categoria_desastres as ecd', 'ed.entrada_categoria_desastre_id', '=', 'ecd.id')
            ->join('dec_desastre_item_campos as dic', 'ed.item_campo_id', '=', 'dic.id')
            ->join('dec_desastre_items as di', 'dic.desastre_item_id', '=', 'di.id')
            ->join('dec_desastre_categorias as dc', 'di.categoria_id', '=', 'dc.id')
            ->whereIn('ecd.entrada_processo_id', $processoIds)
            ->where('dc.id', self::CAT_DANOS_HUMANOS_ID)
            ->select(
                'ecd.entrada_processo_id',
                'ed.municipio_id',
                'di.id as item_id',
                DB::raw('CAST(COALESCE(ed.valor, 0) AS UNSIGNED) as valor_numerico')
            )
            ->get();

        // Agrupa por processo
        $totalsByProcesso = $allTotals->groupBy('entrada_processo_id');
        $dhByProcesso = $danosHumanosBatch->groupBy('entrada_processo_id');

        foreach ($processos as $processo) {
            $id = $processo->id;
            $processoTotals = $totalsByProcesso->get($id, collect());
            $processoDH = $dhByProcesso->get($id, collect());

            // Agrupa por municipio -> categoria -> campo
            $groupedByMunicipio = $processoTotals->groupBy('municipio_id')->map(function ($municipioItems) {
                return $municipioItems->groupBy('categoria_titulo')->map(function ($categoriaItems) {
                    return $categoriaItems->keyBy('desastre_campo_titulo')->map(function ($item) {
                        return $item->tipo === 'currency' ? (float) $item->total_valor : (int) $item->total_valor;
                    });
                });
            });

            // Danos humanos por municipio
            $danosHumanosByMunicipio = $processoDH->groupBy('municipio_id')->map(function ($municipioItems) {
                $result = array_fill_keys(array_unique(array_values(self::DANOS_HUMANOS_MAP)), 0);
                foreach ($municipioItems as $item) {
                    $key = self::DANOS_HUMANOS_MAP[(int) $item->item_id] ?? null;
                    if ($key) {
                        $result[$key] += (int) $item->valor_numerico;
                    }
                }
                $result['total'] = array_sum($result);
                return $result;
            });

            $geral = $this->aggregateTotaisGeralFromGrouped($groupedByMunicipio, $danosHumanosByMunicipio);
            $porMunicipio = $this->aggregateTotaisPorMunicipioFromGrouped($groupedByMunicipio, $danosHumanosByMunicipio, $processoTotals);

            $processo->totais = [
                'geral' => $geral,
                'por_municipio' => $porMunicipio,
            ];

            // Tipo desastre completo para modal de detalhes
            $tipoDesastre = $this->getTipoDesastreCompleto($processo->tipo_desastre_id);
            if ($tipoDesastre) {
                $processo->tipo_desastre_info = $tipoDesastre;
            }
        }
    }

    /**
     * Calcula totais de desastres para um processo especifico.
     * Usa a mesma logica do ProcessoExportService para garantir consistencia.
     *
     * @param int $processoId ID do processo
     * @return array Totais formatados (geral e por municipio)
     */
    public function calculateTotaisForProcesso(int $processoId): array
    {
        $processoIds = collect([$processoId]);

        // Query identica ao ProcessoExportService
        $allTotals = DB::table('dec_entrada_categoria_desastres as ecd')
            ->join('dec_entrada_desastres as ed', 'ecd.id', '=', 'ed.entrada_categoria_desastre_id')
            ->join('dec_desastre_item_campos as dic', 'ed.item_campo_id', '=', 'dic.id')
            ->join('dec_desastre_categorias as dc', 'ecd.categoria_id', '=', 'dc.id')
            ->leftJoin('municipios as m', 'ed.municipio_id', '=', 'm.id')
            ->whereIn('ecd.entrada_processo_id', $processoIds)
            ->whereIn('dic.tipo', ['number', 'currency'])
            ->select(
                'ed.municipio_id',
                'm.nome as municipio_nome',
                'dc.titulo as categoria_titulo',
                'dic.titulo as desastre_campo_titulo',
                'dic.tipo',
                DB::raw('SUM(ed.valor) as total_valor')
            )
            ->groupBy('ed.municipio_id', 'm.nome', 'dc.titulo', 'dic.titulo', 'dic.tipo')
            ->get();

        // Agrupa identico ao ExportService: municipio -> categoria -> campo
        $groupedByMunicipio = $allTotals->groupBy('municipio_id')->map(function ($municipioItems) {
            return $municipioItems->groupBy('categoria_titulo')->map(function ($categoriaItems) {
                return $categoriaItems->keyBy('desastre_campo_titulo')->map(function ($item) {
                    return $item->tipo === 'currency' ? (float) $item->total_valor : (int) $item->total_valor;
                });
            });
        });

        // Danos humanos separado (categoria ID = 1)
        $danosHumanosByMunicipio = $this->calculateDanosHumanosForProcesso($processoIds);

        // Agrega totais gerais
        $geral = $this->aggregateTotaisGeralFromGrouped($groupedByMunicipio, $danosHumanosByMunicipio);

        // Agrega por municipio
        $porMunicipio = $this->aggregateTotaisPorMunicipioFromGrouped($groupedByMunicipio, $danosHumanosByMunicipio, $allTotals);

        return [
            'geral' => $geral,
            'por_municipio' => $porMunicipio,
        ];
    }

    /**
     * Calcula danos humanos detalhados para um processo.
     *
     * @param \Illuminate\Support\Collection $processoIds IDs dos processos
     * @return \Illuminate\Support\Collection Danos humanos por municipio
     */
    private function calculateDanosHumanosForProcesso($processoIds): \Illuminate\Support\Collection
    {
        $danosHumanos = DB::table('dec_entrada_desastres as ed')
            ->join('dec_entrada_categoria_desastres as ecd', 'ed.entrada_categoria_desastre_id', '=', 'ecd.id')
            ->join('dec_desastre_item_campos as dic', 'ed.item_campo_id', '=', 'dic.id')
            ->join('dec_desastre_items as di', 'dic.desastre_item_id', '=', 'di.id')
            ->join('dec_desastre_categorias as dc', 'di.categoria_id', '=', 'dc.id')
            ->whereIn('ecd.entrada_processo_id', $processoIds)
            ->where('dc.id', self::CAT_DANOS_HUMANOS_ID)
            ->select(
                'ed.municipio_id',
                'di.id as item_id',
                'di.titulo as desastre_item_titulo',
                DB::raw('CAST(COALESCE(ed.valor, 0) AS UNSIGNED) as valor_numerico')
            )
            ->get();

        return $danosHumanos->groupBy('municipio_id')->map(function ($municipioItems) {
            $result = array_fill_keys(array_unique(array_values(self::DANOS_HUMANOS_MAP)), 0);

            foreach ($municipioItems as $item) {
                // Tenta pelo ID primeiro (mapeamento legado/fixo)
                $key = self::DANOS_HUMANOS_MAP[(int) $item->item_id] ?? null;
                
                // Fallback pelo título (mais robusto para bancos externos/dinâmicos)
                if (!$key && isset($item->desastre_item_titulo)) {
                    $tituloNormalizado = strtolower(Str::ascii($item->desastre_item_titulo));
                    foreach (self::DANOS_HUMANOS_TITLES as $mapTitle => $mapKey) {
                        if (str_contains($tituloNormalizado, $mapTitle)) {
                            $key = $mapKey;
                            break;
                        }
                    }
                }

                if ($key) {
                    $result[$key] += (int) $item->valor_numerico;
                }
            }

            $result['total'] = array_sum($result);
            return $result;
        });
    }

    /**
     * Agrega totais gerais usando estrutura agrupada (mesmo padrao do ExportService).
     *
     * @param \Illuminate\Support\Collection $groupedByMunicipio Dados agrupados por municipio->categoria->campo
     * @param \Illuminate\Support\Collection $danosHumanos Danos humanos por municipio
     * @return array Totais agregados
     */
    private function aggregateTotaisGeralFromGrouped($groupedByMunicipio, $danosHumanos): array
    {
        // Soma danos humanos de todos os municipios
        $totaisDanosHumanos = [
            'total' => 0,
            'obitos' => 0,
            'feridos' => 0,
            'desalojados' => 0,
            'desabrigados' => 0,
            'desaparecidos' => 0,
            'outros_afetados' => 0,
        ];

        foreach ($danosHumanos as $municipioDanos) {
            foreach ($municipioDanos as $key => $valor) {
                if (isset($totaisDanosHumanos[$key])) {
                    $totaisDanosHumanos[$key] += $valor;
                }
            }
        }
        $totaisDanosHumanos['total'] = $totaisDanosHumanos['obitos'] + $totaisDanosHumanos['feridos']
            + $totaisDanosHumanos['desalojados'] + $totaisDanosHumanos['desabrigados']
            + $totaisDanosHumanos['desaparecidos'] + $totaisDanosHumanos['outros_afetados'];

        // Inicializa totais
        $danosMateriais = ['quantidade' => 0, 'valor' => 0];
        $prejuizosPublicos = ['total' => 0];
        $prejuizosPrivados = ['total' => 0];

        // Soma de todos os municipios usando campos exatos (mesmo padrao do ExportService)
        foreach ($groupedByMunicipio as $municipioData) {
            // DANOS MATERIAIS - acessa campos exatos
            if (isset($municipioData[self::CAT_DANOS_MATERIAIS])) {
                $dm = $municipioData[self::CAT_DANOS_MATERIAIS];
                $danosMateriais['quantidade'] += ($dm['Quantidades danificadas'] ?? 0) + ($dm['Quantidades destruídas'] ?? $dm['Quantidades destruidas'] ?? 0);
                $danosMateriais['valor'] += $dm['Valor (R$)'] ?? 0;
            }

            // PREJUIZOS ECONOMICOS PUBLICOS
            if (isset($municipioData[self::CAT_PREJUIZOS_PUBLICOS])) {
                $pp = $municipioData[self::CAT_PREJUIZOS_PUBLICOS];
                $prejuizosPublicos['total'] += $pp['Valor do prejuízo (R$)'] ?? $pp['Valor do prejuizo (R$)'] ?? 0;
            }

            // PREJUIZOS ECONOMICOS PRIVADOS
            if (isset($municipioData[self::CAT_PREJUIZOS_PRIVADOS])) {
                $ppv = $municipioData[self::CAT_PREJUIZOS_PRIVADOS];
                $prejuizosPrivados['total'] += $ppv['Valor do prejuízo (R$)'] ?? $ppv['Valor do prejuizo (R$)'] ?? 0;
            }
        }

        return [
            'danos_humanos' => $totaisDanosHumanos,
            'danos_materiais' => $danosMateriais,
            'prejuizos_publicos' => $prejuizosPublicos,
            'prejuizos_privados' => $prejuizosPrivados,
        ];
    }

    /**
     * Agrega totais por municipio usando estrutura agrupada.
     *
     * @param \Illuminate\Support\Collection $groupedByMunicipio Dados agrupados
     * @param \Illuminate\Support\Collection $danosHumanos Danos humanos por municipio
     * @param \Illuminate\Support\Collection $allTotals Query original para nomes
     * @return array Totais por municipio
     */
    private function aggregateTotaisPorMunicipioFromGrouped($groupedByMunicipio, $danosHumanos, $allTotals): array
    {
        // Mapa de nomes de municipios
        $municipioNomes = [];
        foreach ($allTotals as $item) {
            $municipioNomes[$item->municipio_id] = $item->municipio_nome;
        }

        $municipios = [];

        // Processa cada municipio
        $allMunicipioIds = $groupedByMunicipio->keys()->merge($danosHumanos->keys())->unique();

        foreach ($allMunicipioIds as $munId) {
            $municipioData = $groupedByMunicipio[$munId] ?? collect();
            $dh = $danosHumanos[$munId] ?? [];

            // Calcula danos humanos
            $danosHumanosTotal = is_array($dh) ? array_merge(['total' => array_sum($dh)], $dh) : ['total' => 0];

            // Calcula danos materiais
            $dm = $municipioData[self::CAT_DANOS_MATERIAIS] ?? [];
            $danosMateriais = [
                'quantidade' => ($dm['Quantidades danificadas'] ?? 0) + ($dm['Quantidades destruídas'] ?? $dm['Quantidades destruidas'] ?? 0),
                'valor' => $dm['Valor (R$)'] ?? 0,
            ];

            // Prejuizos publicos
            $pp = $municipioData[self::CAT_PREJUIZOS_PUBLICOS] ?? [];
            $prejuizosPublicos = ['total' => $pp['Valor do prejuízo (R$)'] ?? $pp['Valor do prejuizo (R$)'] ?? 0];

            // Prejuizos privados
            $ppv = $municipioData[self::CAT_PREJUIZOS_PRIVADOS] ?? [];
            $prejuizosPrivados = ['total' => $ppv['Valor do prejuízo (R$)'] ?? $ppv['Valor do prejuizo (R$)'] ?? 0];

            $municipios[] = [
                'municipio_id' => $munId,
                'municipio_nome' => $municipioNomes[$munId] ?? null,
                'totais' => [
                    'danos_humanos' => $danosHumanosTotal,
                    'danos_materiais' => $danosMateriais,
                    'prejuizos_publicos' => $prejuizosPublicos,
                    'prejuizos_privados' => $prejuizosPrivados,
                ],
            ];
        }

        return $municipios;
    }

    /**
     * Obtem dados completos do tipo de desastre (COBRADE).
     *
     * @param int|null $tipoDesastreId ID do tipo de desastre
     * @return array|null Dados completos do tipo de desastre
     */
    public function getTipoDesastreCompleto(?int $tipoDesastreId): ?array
    {
        if (!$tipoDesastreId) {
            return null;
        }

        $cobrade = $this->getClassificacaoDesastres();
        $match = $cobrade->firstWhere('id', $tipoDesastreId);

        if (!$match) {
            return null;
        }

        return [
            'id' => $match['id'] ?? null,
            'cobrade' => $match['cobrade'] ?? null,
            'categoria' => $match['categoria'] ?? null,
            'grupo' => $match['grupo'] ?? null,
            'subgrupo' => $match['subgrupo'] ?? null,
            'tipo' => $match['tipo'] ?? null,
            'subtipo' => $match['subtipo'] ?? null,
            'nome' => $match['a_definicao'] ?? $match['subtipo'] ?? $match['tipo'] ?? null,
            'definicao' => $match['a_definicao'] ?? null,
        ];
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
