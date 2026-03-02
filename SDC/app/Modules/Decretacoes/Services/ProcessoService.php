<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes\Services;

use App\Http\Filters\EntradaProcessoFilter;
use App\Http\Resources\Decretacoes\ProcessosIndexResource;
use App\Models\Decreto\DecretoMunicipio;
use App\Models\Decreto\DesastreGrupo;
use App\Models\Decreto\EntradaDecreto;
use App\Models\Decreto\EntradaDesastre;
use App\Models\Decreto\EntradaProcesso;
use App\Modules\Decretacoes\DTOs\ProcessoDTO;
use App\Modules\Decretacoes\Models\Processo;
use App\Modules\Shared\BaseService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * ProcessoService - Consolidated service for Decretacoes module.
 *
 * This service consolidates the following services:
 * - ProcessoService (CRUD)
 * - ProcessoFilterService (filtering logic)
 * - ProcessoStatisticsService (statistics)
 * - ProcessoSyncService (municipality sync)
 * - ProcessoExportService (PowerBI export)
 * - EntradaProcessoService (auxiliary/orchestrator methods)
 */
class ProcessoService extends BaseService
{
    public function __construct(
        private readonly HexagonIntegrationService $hexagonService
    ) {
    }

    // =========================================================================
    // REGION: CRUD Methods (from original ProcessoService)
    // =========================================================================

    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Processo::query()->with(['anexos', 'logs', 'municipios']);

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('numero_processo', 'like', "%{$filters['search']}%")
                  ->orWhere('municipio', 'like', "%{$filters['search']}%");
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['tipo_decreto'])) {
            $query->where('tipo_decreto', $filters['tipo_decreto']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function findById(int $id): ?Processo
    {
        return Processo::with(['anexos', 'danosHumanos', 'danosMateriais', 'prejuizos', 'logs'])->find($id);
    }

    public function create(array $data): Processo
    {
        return Processo::create($data);
    }

    public function update(int $id, array $data): bool
    {
        $processo = Processo::find($id);
        if (!$processo) {
            return false;
        }
        return $processo->update($data);
    }

    public function delete(int $id): bool
    {
        $processo = Processo::find($id);
        if (!$processo) {
            return false;
        }
        return $processo->delete();
    }

    /**
     * Create a new entrada processo using DTO.
     */
    public function createProcesso(ProcessoDTO $dto): EntradaProcesso
    {
        return DB::transaction(function () use ($dto) {
            $processo = EntradaProcesso::create($dto->allData);

            $this->syncMunicipalities($processo, $dto->municipios);
            $this->syncInformacoesDecreto($processo, $dto->informacoesDecreto);

            return $processo;
        });
    }

    /**
     * Update an entrada processo using DTO.
     */
    public function updateProcesso(ProcessoDTO $dto, int $id): EntradaProcesso
    {
        return DB::transaction(function () use ($dto, $id) {
            $processo = EntradaProcesso::findOrFail($id);
            $processo->fill($dto->allData);

            $this->syncMunicipalities($processo, $dto->municipios);
            $this->syncInformacoesDecreto($processo, $dto->informacoesDecreto);

            $processo->save();

            if ($processo->wasChanged()) {
                $this->hexagonService->updateHexagonService($processo);
            }

            return $processo;
        });
    }

    // =========================================================================
    // REGION: Filter Methods (from ProcessoFilterService)
    // =========================================================================

    /**
     * Apply filters to EntradaProcesso query.
     */
    public function applyFilters(Request $request): Builder
    {
        $query = EntradaProcesso::query();

        $filter = new EntradaProcessoFilter($request);
        $filteredQuery = $filter->apply($query);

        $filteredQuery->orderBy('data_entrada', 'desc');

        return $filteredQuery;
    }

    /**
     * Get available filter options.
     */
    public function getFilterOptions(): array
    {
        return EntradaProcessoFilter::getFilterOptions();
    }

    /**
     * Get summary of active filters.
     */
    public function getActiveFiltersSummary(Request $request): array
    {
        $activeFilters = [];

        $filterLabels = [
            'search' => 'Busca',
            'data_entrada' => 'Data Entrada',
            'data_entrada_inicio' => 'Data Entrada Inicio',
            'data_entrada_fim' => 'Data Entrada Fim',
            'processo' => 'Tipo Processo',
            'reconhecimento' => 'Status',
            'analista' => 'Analista',
            'situacao_anormalidade' => 'Situacao',
            'data_decreto_inicio' => 'Data Decreto Inicio',
            'data_decreto_fim' => 'Data Decreto Fim',
            'vigencia_status' => 'Vigencia',
            'tipo_desastre_id' => 'Tipo Desastre',
            'municipio_id' => 'Municipio',
            'n_protocolo_fide' => 'Protocolo FIDE'
        ];

        $cobrade = collect(include app_path('Enums/classificacao_desastres.php'));

        foreach ($filterLabels as $param => $label) {
            if ($request->filled($param)) {
                $value = $request->input($param);
                $entry = [
                    'param' => $param,
                    'label' => $label,
                    'value' => $value,
                ];

                if ($param === 'tipo_desastre_id') {
                    $match = $cobrade->firstWhere('id', (int) $value);
                    if ($match) {
                        $labelParts = array_filter([
                            $match['cobrade'] ?? null,
                            $match['a_definicao']
                                ?? $match['subtipo']
                                ?? $match['tipo']
                                ?? $match['subgrupo']
                                ?? $match['grupo']
                                ?? null,
                        ]);
                        $entry['display_value'] = implode(' - ', $labelParts);
                    }
                }

                $activeFilters[] = $entry;
            }
        }

        return $activeFilters;
    }

    /**
     * Check if any filters are currently active.
     */
    public function hasActiveFilters(Request $request): bool
    {
        $filterParams = [
            'search', 'data_entrada', 'data_entrada_inicio', 'data_entrada_fim', 'processo', 'reconhecimento',
            'analista', 'situacao_anormalidade', 'data_decreto_inicio',
            'data_decreto_fim', 'vigencia_status', 'tipo_desastre_id', 'municipio_id', 'n_protocolo_fide'
        ];

        return $request->hasAny($filterParams);
    }

    /**
     * Get filtered and paginated entrada processos.
     */
    public function getFilteredProcessos(Request $request): array
    {
        $query = $this->applyFilters($request);

        $data = (new ProcessosIndexResource($query))->toArray();
        $data['filter_options'] = $this->getFilterOptions();

        return $data;
    }

    // =========================================================================
    // REGION: Statistics Methods (from ProcessoStatisticsService)
    // =========================================================================

    /**
     * Get basic statistics (legacy method).
     */
    public function getStatistics(): array
    {
        return [
            'total' => Processo::count(),
            'em_analise' => Processo::where('status', 'em_analise')->count(),
            'aprovados' => Processo::where('status', 'aprovado')->count(),
            'rejeitados' => Processo::where('status', 'rejeitado')->count(),
        ];
    }

    /**
     * Get dashboard statistics.
     */
    public function getDashboardStatistics(): array
    {
        return [
            'total_processos' => EntradaProcesso::count(),
            'processos_vigentes' => $this->getVigentesCount(),
            'processos_municipais' => EntradaProcesso::where('processo', 'MUNICIPAL')->count(),
            'processos_estaduais' => EntradaProcesso::where('processo', 'ESTADUAL')->count(),
        ];
    }

    /**
     * Get count of vigentes processos.
     */
    protected function getVigentesCount(): int
    {
        return EntradaProcesso::where(function ($q) {
            $q->whereNull('data_publicacao_mg')
              ->orWhereRaw('DATE_ADD(data_publicacao_mg, INTERVAL prazo_vigencia DAY) >= CURDATE()');
        })->count();
    }

    // =========================================================================
    // REGION: Sync Methods (from ProcessoSyncService)
    // =========================================================================

    /**
     * Sync municipalities for a processo.
     */
    public function syncMunicipalities(EntradaProcesso $processo, array $municipios): void
    {
        $municipios = array_map('intval', $municipios);

        $currentMunicipios = DecretoMunicipio::where('entrada_processos_id', $processo->id)
            ->pluck('municipio_id')
            ->toArray();

        $municipiosToRemove = array_diff($currentMunicipios, $municipios);
        $municipiosToAdd = array_diff($municipios, $currentMunicipios);

        if (!empty($municipiosToRemove)) {
            DecretoMunicipio::where('entrada_processos_id', $processo->id)
                ->whereIn('municipio_id', $municipiosToRemove)
                ->delete();

            EntradaDesastre::where('entrada_processo_id', $processo->id)
                ->whereIn('municipio_id', $municipiosToRemove)
                ->delete();
        }

        if (!empty($municipiosToAdd)) {
            $protocoloFide = (count($municipios) === 1) ? ($processo->n_protocolo_fide ?? null) : null;

            foreach ($municipiosToAdd as $municipioId) {
                DecretoMunicipio::create([
                    'entrada_processos_id' => $processo->id,
                    'municipio_id' => $municipioId,
                    'n_protocolo_fide' => $protocoloFide
                ]);
            }
        }
    }

    /**
     * Sync informacoes decreto for a processo.
     */
    public function syncInformacoesDecreto(EntradaProcesso $processo, ?string $informacoesDecreto): void
    {
        EntradaDecreto::where('entrada_processos_id', $processo->id)->delete();

        if ($informacoesDecreto) {
            $infDecretos = json_decode($informacoesDecreto);
            if ($infDecretos) {
                foreach ($infDecretos as $infDecreto) {
                    EntradaDecreto::create([
                        'entrada_processos_id' => $processo->id,
                        'decreto_categoria_id' => $infDecreto->id,
                        'observacao' => $infDecreto->observacao
                    ]);
                }
            }
        }
    }

    // =========================================================================
    // REGION: Export PowerBI Methods (from ProcessoExportService)
    // =========================================================================

    /**
     * Get normalized data for Power BI export.
     */
    public function getNormalizedDataForPowerBI(Request $request): array
    {
        $query = $this->applyFilters($request);

        if ($request->input('include_deleted', false)) {
            $query->withTrashed();
        }

        $entradas = $query->get();
        $normalizedData = [];

        foreach ($entradas as $entrada) {
            $entrada->load(['municipios', 'desastres']);
            $desastreTotals = $this->calculateDesastreTotalsForEntry($entrada);

            if ($entrada->municipios->isEmpty()) {
                $normalizedData[] = $this->buildExportRow($entrada, null, [], []);
                continue;
            }

            foreach ($entrada->municipios as $municipio) {
                $municipioTotals = $desastreTotals['por_municipio'][$municipio->id] ?? [];
                $danosHumanos = $desastreTotals['danos_humanos_por_municipio'][$municipio->id] ?? [];
                $normalizedData[] = $this->buildExportRow($entrada, $municipio, $municipioTotals, $danosHumanos);
            }
        }

        return $normalizedData;
    }

    /**
     * Build a row for Power BI export.
     */
    private function buildExportRow(EntradaProcesso $entrada, $municipio, array $municipioTotals, array $danosHumanos): array
    {
        $row = [
            'id' => $entrada->id,
            'uf' => 'MG',
            'municipio' => $municipio ? ($municipio->p_nome ?? $municipio->nome) : null,
            'codigo_ibge' => $municipio->Codmundv ?? null,
            'macroregiao' => $municipio->macroregiao ?? null,
            'latitude' => $municipio->latitude ?? null,
            'longitude' => $municipio->longitude ?? null,
            'latitude_dec' => $municipio->latitude_dec ?? null,
            'longitude_dec' => $municipio->longitude_dec ?? null,
            'data_registro' => $entrada->data_entrada,
            'data_criacao' => $entrada->created_at,
            'deletado' => $entrada->trashed(),
            'data_delecao' => $entrada->deleted_at,
            'protocolo' => $entrada->n_protocolo_fide,
            'cobrade' => $entrada->tipo_desastre_cobrade,
            'tipo_desastre' => $entrada->tipo_desastre_nome,
            'status' => $entrada->reconhecimento,
            'data_fato' => $entrada->data_ocorrencia_desastre,
            'data_decreto_municipal' => $entrada->data_decreto_municipal,
            'data_publicacao_mg' => $entrada->data_publicacao_mg,
            'prazo_vigencia_dias' => $entrada->prazo_vigencia,
            'data_vencimento' => $entrada->data_vencimento,
            'dias_restantes' => $entrada->dias_restantes,
            'tipo_decreto' => $this->mapearTipoDecreto($entrada->situacao_anormalidade),
            'processo' => $entrada->processo,
            'analista' => $entrada->analista,
            'obitos' => $danosHumanos['obitos'] ?? 0,
            'feridos' => $danosHumanos['feridos'] ?? 0,
            'desalojados' => $danosHumanos['desalojados'] ?? 0,
            'desabrigados' => $danosHumanos['desabrigados'] ?? 0,
            'desaparecidos' => $danosHumanos['desaparecidos'] ?? 0,
            'outros_afetados' => $danosHumanos['outros_afetados'] ?? 0,
        ];

        $row['danos_humanos_quantidade'] = array_sum([
            $row['obitos'], $row['feridos'], $row['desalojados'],
            $row['desabrigados'], $row['desaparecidos'], $row['outros_afetados']
        ]);

        $row['danos_materiais_danificadas'] = $municipioTotals['DANOS MATERIAIS']['Quantidades danificadas'] ?? 0;
        $row['danos_materiais_destruidas'] = $municipioTotals['DANOS MATERIAIS']['Quantidades destruidas'] ?? 0;
        $row['danos_materiais_valor'] = $municipioTotals['DANOS MATERIAIS']['Valor (R$)'] ?? 0;

        $row['prejuizos_publicos_valor'] = $municipioTotals['PREJUIZOS ECONOMICOS PUBLICOS']['Valor do prejuizo (R$)'] ?? 0;
        $row['prejuizos_privados_valor'] = $municipioTotals['PREJUIZOS ECONOMICOS PRIVADOS']['Valor do prejuizo (R$)'] ?? 0;

        return $row;
    }

    /**
     * Calculate desastre totals for a single entry.
     */
    private function calculateDesastreTotalsForEntry(EntradaProcesso $entrada): array
    {
        $processoIds = collect([$entrada->id]);

        $allTotals = DB::table('dec_entrada_categoria_desastres as ecd')
            ->join('dec_entrada_desastres as ed', 'ecd.id', '=', 'ed.entrada_categoria_desastre_id')
            ->join('dec_desastre_item_campos as dic', 'ed.item_campo_id', '=', 'dic.id')
            ->join('dec_desastre_categorias as dc', 'ecd.categoria_id', '=', 'dc.id')
            ->join('cedec_municipio as m', 'ed.municipio_id', '=', 'm.id')
            ->whereIn('ecd.entrada_processo_id', $processoIds)
            ->whereIn('dic.tipo', ['number', 'currency'])
            ->select(
                'ed.municipio_id',
                'dc.titulo as categoria_titulo',
                'dic.titulo as desastre_campo_titulo',
                'dic.tipo',
                DB::raw('SUM(ed.valor) as total_valor')
            )
            ->groupBy('ed.municipio_id', 'dc.titulo', 'dic.titulo', 'dic.tipo')
            ->get();

        $groupedByMunicipio = $allTotals->groupBy('municipio_id')->map(function ($municipioItems) {
            return $municipioItems->groupBy('categoria_titulo')->map(function ($categoriaItems) {
                return $categoriaItems->keyBy('desastre_campo_titulo')->map(function ($item) {
                    return $item->tipo === 'currency' ? (float) $item->total_valor : (int) $item->total_valor;
                });
            });
        });

        $danosHumanosByMunicipio = $this->calculateDanosHumanos($processoIds);

        return [
            'por_municipio' => $groupedByMunicipio->toArray(),
            'danos_humanos_por_municipio' => $danosHumanosByMunicipio->toArray()
        ];
    }

    /**
     * Calculate danos humanos for processos.
     */
    private function calculateDanosHumanos($processoIds): Collection
    {
        $danosHumanos = DB::table('dec_entrada_desastres as ed')
            ->join('dec_entrada_categoria_desastres as ecd', 'ed.entrada_categoria_desastre_id', '=', 'ecd.id')
            ->join('dec_desastre_item_campos as dic', 'ed.item_campo_id', '=', 'dic.id')
            ->join('dec_desastre_items as di', 'dic.desastre_item_id', '=', 'di.id')
            ->join('dec_desastre_categorias as dc', 'di.categoria_id', '=', 'dc.id')
            ->whereIn('ecd.entrada_processo_id', $processoIds)
            ->where('dc.id', 1)
            ->whereNull('ed.deleted_at')
            ->select(
                'ed.municipio_id',
                'di.id as item_id',
                'di.titulo as item_titulo',
                DB::raw('CAST(COALESCE(ed.valor, 0) AS UNSIGNED) as valor_numerico')
            )
            ->get();

        return $danosHumanos->groupBy('municipio_id')->map(function ($municipioItems) {
            $result = [
                'obitos' => 0, 'feridos' => 0, 'desalojados' => 0,
                'desabrigados' => 0, 'desaparecidos' => 0, 'outros_afetados' => 0,
            ];

            foreach ($municipioItems as $item) {
                $itemId = (int) $item->item_id;
                $valor = (int) $item->valor_numerico;

                switch ($itemId) {
                    case 1: $result['obitos'] += $valor; break;
                    case 2: case 3: $result['feridos'] += $valor; break;
                    case 4: $result['desabrigados'] += $valor; break;
                    case 5: $result['desalojados'] += $valor; break;
                    case 6: $result['desaparecidos'] += $valor; break;
                    case 7: $result['outros_afetados'] += $valor; break;
                }
            }

            return $result;
        });
    }

    /**
     * Map situacao anormalidade to tipo decreto.
     */
    protected function mapearTipoDecreto($situacaoAnormalidade): ?string
    {
        return in_array($situacaoAnormalidade, ['SE', 'ECP'], true) ? $situacaoAnormalidade : null;
    }

    // =========================================================================
    // REGION: Auxiliary Methods (from EntradaProcessoService)
    // =========================================================================

    /**
     * Prepare processo data for editing.
     */
    public function prepareProcessoForEdit(EntradaProcesso $processo): EntradaProcesso
    {
        $classificacaoDesastres = collect(include(app_path('Enums/classificacao_desastres.php')))
            ->sortBy('a_definicao')
            ->values();

        if ($processo->tipo_desastre_id) {
            $processo->desastre = $classificacaoDesastres->firstWhere('id', $processo->tipo_desastre_id);
        }

        return $processo;
    }

    /**
     * Load disaster data for municipalities.
     */
    public function loadMunicipiosWithDesastreData(EntradaProcesso $processo): Collection
    {
        $processoId = $processo->id;

        return $processo->municipios->transform(function ($municipio) use ($processoId) {
            $municipioId = $municipio->id;

            $decretoMunicipio = DecretoMunicipio::where('entrada_processos_id', $processoId)
                ->where('municipio_id', $municipioId)
                ->first();

            $municipio->n_protocolo_fide = $decretoMunicipio?->n_protocolo_fide;
            $municipio->updated_at = $decretoMunicipio?->updated_at;

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
     * Load informacoes decreto for a processo.
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

    /**
     * Get totals for disaster data from entradas collection.
     */
    public function getTotalDesastresCountFromEntradas(Collection $entradas): Collection
    {
        $processoIds = $entradas->pluck('id');

        if ($processoIds->isEmpty()) {
            return $entradas;
        }

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

        $groupedTotalsPorMunicipio = $allTotals->groupBy('entrada_processo_id')->map(function ($processoItems) {
            return $processoItems->groupBy('municipio')->map(function ($municipioItems) {
                return $municipioItems->groupBy('categoria_titulo')->map(function ($categoriaItems) {
                    return $categoriaItems->keyBy('desastre_campo_titulo')->map(function ($item) {
                        return $item->tipo === 'currency' ? number_format($item->total_valor, 2, ',', '.') : $item->total_valor;
                    });
                });
            });
        });

        $groupedTotals = $allTotals->groupBy('entrada_processo_id')->map(function ($processoItems) {
            return $processoItems->groupBy('categoria_titulo')->map(function ($categoriaItems) {
                return $categoriaItems->groupBy('desastre_campo_titulo')->map(function ($items) {
                    $total = $items->sum('total_valor');
                    $tipo = $items->first()->tipo;
                    return $tipo === 'currency' ? number_format($total, 2, ',', '.') : $total;
                });
            });
        });

        $entradas->each(function ($entrada) use ($groupedTotals, $groupedTotalsPorMunicipio) {
            $entrada->desastre_totals = $groupedTotals->get($entrada->id, collect());
            $entrada->desastre_totals_por_municipio = $groupedTotalsPorMunicipio->get($entrada->id, collect());
        });

        return $entradas;
    }

    /**
     * Get pedido data from SDC database.
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
                'codigo' => $item->codigo,
                'status' => $item->status,
                'descricao_item' => $item->descricao_item,
                'tp_item' => $item->tp_item,
                'total_qtd' => $item->total_qtd,
            ]);
        });
    }
}
