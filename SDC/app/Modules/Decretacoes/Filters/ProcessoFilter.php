<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes\Filters;

use App\Models\Municipio;
use App\Modules\Decretacoes\Models\DecretoMunicipio;
use App\Modules\Decretacoes\Models\Processo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ProcessoFilter
{
    protected Request $request;
    protected Builder $builder;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function apply(Builder $builder): Builder
    {
        $this->builder = $builder;

        $this->applyBasicFilters()
             ->applyAdvancedFilters();

        return $this->builder;
    }

    protected function applyBasicFilters(): self
    {
        $this->filterBySearch()
             ->filterByDataEntrada()
             ->filterByDateRange()
             ->filterByProcesso()
             ->filterByID();

        return $this;
    }

    protected function applyAdvancedFilters(): self
    {
        $this->filterByReconhecimento()
             ->filterByTipoLancamento()
             ->filterByAnalista()
             ->filterBySituacaoAnormalidade()
             ->filterByDataDecretoRange()
             ->filterByVigenciaStatus()
             ->filterByMunicipio()
             ->filterByProtocoloFide()
             ->filterByTipoDesastre()
             ->filterByAno()
             ->filterByMes();

        return $this;
    }

    protected function filterByID(): self
    {
        if ($this->request->filled('entrada_id')) {
            $this->builder->whereId($this->request->input('entrada_id'));
        }

        return $this;
    }

    protected function filterBySearch(): self
    {
        if ($this->request->filled('search')) {
            $search = $this->request->input('search');
            $table = $this->builder->getModel()->getTable();

            $this->builder->where(function ($q) use ($search, $table) {
                $q->where('analista', 'like', "%$search%")
                  ->orWhere('n_protocolo_fide', 'like', "%$search%")
                  ->orWhereExists(function ($sub) use ($search, $table) {
                      $sub->select(DB::raw(1))
                          ->from('dec_decreto_municipios as dm')
                          ->join('municipios as m', 'm.id', '=', 'dm.municipio_id')
                          ->whereColumn('dm.entrada_processos_id', "{$table}.id")
                          ->where(function ($w) use ($search) {
                              $w->where('m.nome', 'like', "%$search%")
                                ->orWhere('m.codigo_ibge', 'like', "%$search%");
                          });
                  });
            });
        }

        return $this;
    }

    protected function filterByDataEntrada(): self
    {
        if ($this->request->filled('data_entrada')) {
            $this->builder->whereDate('data_entrada', $this->request->input('data_entrada'));
        }

        return $this;
    }

    protected function filterByDateRange(): self
    {
        $dataInicio = $this->request->input('data_entrada_inicio') ?? $this->request->input('data_inicio');
        $dataFim = $this->request->input('data_entrada_fim') ?? $this->request->input('data_fim');

        if ($dataInicio) {
            $this->builder->whereDate('data_entrada', '>=', $dataInicio);
        }

        if ($dataFim) {
            $this->builder->whereDate('data_entrada', '<=', $dataFim);
        }

        return $this;
    }

    protected function filterByProcesso(): self
    {
        if ($this->request->filled('processo')) {
            $this->builder->where('processo', $this->request->input('processo'));
        }

        return $this;
    }

    protected function filterByReconhecimento(): self
    {
        if ($this->request->filled('reconhecimento')) {
            $reconhecimentos = explode(',', $this->request->input('reconhecimento'));
            $this->builder->whereIn('reconhecimento', $reconhecimentos);
        }

        return $this;
    }

    /**
     * Separa registros de decretacoes pelo mesmo criterio usado nos cards de
     * estatistica, para que clicar no card e a listagem resultante contem a
     * mesma historia.
     */
    protected function filterByTipoLancamento(): self
    {
        if ($this->request->filled('tipo_lancamento')) {
            switch ($this->request->input('tipo_lancamento')) {
                case 'registro':
                    $this->builder->where('reconhecimento', 'Registro');
                    break;

                case 'decretacao':
                    $this->builder->where('reconhecimento', '!=', 'Registro');
                    break;
            }
        }

        return $this;
    }

    protected function filterByAnalista(): self
    {
        if ($this->request->filled('analista')) {
            $this->builder->where('analista', $this->request->input('analista'));
        }

        return $this;
    }

    protected function filterBySituacaoAnormalidade(): self
    {
        if ($this->request->filled('situacao_anormalidade')) {
            $this->builder->where('tipo_desastre', $this->request->input('situacao_anormalidade'));
        }

        return $this;
    }

    protected function filterByDataDecretoRange(): self
    {
        if ($this->request->filled('data_decreto_inicio')) {
            $this->builder->whereDate('data_decreto_municipal', '>=', $this->request->input('data_decreto_inicio'));
        }

        if ($this->request->filled('data_decreto_fim')) {
            $this->builder->whereDate('data_decreto_municipal', '<=', $this->request->input('data_decreto_fim'));
        }

        return $this;
    }

    protected function filterByVigenciaStatus(): self
    {
        if ($this->request->filled('vigencia_status')) {
            $vigenciaStatus = $this->request->input('vigencia_status');

            switch ($vigenciaStatus) {
                case 'vigente':
                    // Mesma definicao do card "Decretacoes Vigentes" em
                    // ProcessoStatsService::applyVigentesConstraints(): dentro
                    // do prazo e sendo decretacao. As duas precisam mudar
                    // juntas, senao clicar no card devolve uma listagem que nao
                    // corresponde ao numero exibido. Registro nao tem vigencia.
                    $this->builder->where(function ($q) {
                        $q->whereNull('data_publicacao_mg')
                          ->orWhereRaw("(data_publicacao_mg + (prazo_vigencia || ' days')::interval) >= CURRENT_DATE");
                    })
                    ->where(function ($q) {
                        $q->where('reconhecimento', '!=', 'Registro')
                          ->orWhereNull('reconhecimento');
                    });
                    break;

                case 'vencido':
                    $this->builder->whereNotNull('data_publicacao_mg')
                                 ->whereRaw("(data_publicacao_mg + (prazo_vigencia || ' days')::interval) < CURRENT_DATE");
                    break;

                case 'proximo_vencer':
                    $this->builder->whereNotNull('data_publicacao_mg')
                                 ->whereRaw("(data_publicacao_mg + (prazo_vigencia || ' days')::interval) >= CURRENT_DATE")
                                 ->whereRaw("(data_publicacao_mg + (prazo_vigencia || ' days')::interval) <= (CURRENT_DATE + INTERVAL '30 days')");
                    break;

                case 'sem_data':
                    $this->builder->whereNull('data_publicacao_mg');
                    break;
            }
        }

        return $this;
    }

    protected function filterByMunicipio(): self
    {
        if ($this->request->filled('municipio_id')) {
            $municipioId = $this->request->input('municipio_id');
            $table = $this->builder->getModel()->getTable();

            $this->builder->whereExists(function ($sub) use ($municipioId, $table) {
                $sub->select(DB::raw(1))
                    ->from('dec_decreto_municipios as dm')
                    ->whereColumn('dm.entrada_processos_id', "{$table}.id")
                    ->where('dm.municipio_id', $municipioId);
            });
        }

        return $this;
    }

    protected function filterByProtocoloFide(): self
    {
        if ($this->request->filled('n_protocolo_fide')) {
            $protocolo = $this->request->input('n_protocolo_fide');
            $table = $this->builder->getModel()->getTable();

            $this->builder->where(function ($q) use ($protocolo, $table) {
                $q->where('n_protocolo_fide', 'like', $protocolo . '%')
                  ->orWhereExists(function ($sub) use ($protocolo, $table) {
                      $sub->select(DB::raw(1))
                          ->from('dec_decreto_municipios as dm')
                          ->whereColumn('dm.entrada_processos_id', "{$table}.id")
                          ->where('dm.n_protocolo_fide', 'like', $protocolo . '%');
                  });
            });
        }

        return $this;
    }

    protected function filterByTipoDesastre(): self
    {
        if ($this->request->filled('tipo_desastre_id')) {
            $desastres = explode(',', $this->request->input('tipo_desastre_id'));
            $this->builder->whereIn('tipo_desastre_id', $desastres);
        }

        return $this;
    }

    protected function filterByAno(): self
    {
        if ($this->request->filled('ano')) {
            $this->builder->whereYear('data_entrada', $this->request->input('ano'));
        }

        return $this;
    }

    protected function filterByMes(): self
    {
        if ($this->request->filled('mes')) {
            $this->builder->whereMonth('data_entrada', $this->request->input('mes'));
        }

        return $this;
    }

    /**
     * Get available filter options for dropdowns.
     */
    public static function getFilterOptions(): array
    {
        $tiposDesastre = collect(include app_path('Enums/classificacao_desastres.php'));

        return [
            'status_options' => \App\Modules\Decretacoes\Enums\StatusProcesso::toSelectOptions(),
            'analistas' => self::getAnalistasOptions(),
            'reconhecimentos' => Processo::distinct('reconhecimento')
                ->whereNotNull('reconhecimento')
                ->where('reconhecimento', '!=', '')
                ->pluck('reconhecimento')
                ->sort()
                ->values(),
            'municipios' => self::getMunicipiosOptions(),
            'redecs' => \App\Modules\Decretacoes\Enums\MockRedec::toSelectOptions(),
            'situacoes_anormalidade' => [
                ['value' => 'ECP', 'label' => 'ECP - Estado de Calamidade Publica'],
                ['value' => 'SE', 'label' => 'SE - Situacao de Emergencia'],
                ['value' => 'N1', 'label' => 'N1 - Nivel 1'],
            ],
            'vigencia_status_options' => [
                ['value' => 'vigente', 'label' => 'Vigente'],
                ['value' => 'vencido', 'label' => 'Vencido'],
                ['value' => 'proximo_vencer', 'label' => 'Proximo ao Vencimento (30 dias)'],
                ['value' => 'sem_data', 'label' => 'Sem Data de Publicacao'],
            ],
            'tipos_desastre' => $tiposDesastre
                ->map(function ($item) {
                    $label = $item['a_definicao']
                        ?? $item['subtipo']
                        ?? $item['tipo']
                        ?? $item['subgrupo']
                        ?? $item['grupo']
                        ?? $item['categoria']
                        ?? 'N/A';

                    return [
                        'id' => $item['id'],
                        'cobrade' => $item['cobrade'] ?? null,
                        'label' => $label,
                        'grupo' => $item['grupo'] ?? null,
                        'subgrupo' => $item['subgrupo'] ?? null,
                        'tipo' => $item['tipo'] ?? null,
                        'subtipo' => $item['subtipo'] ?? null,
                    ];
                })
                ->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)
                ->values(),
            'tipos_desastre_hierarquico' => self::buildCobradeHierarchy($tiposDesastre),
            'cobrade_quick_filters' => self::getCobradeQuickFilters(),
        ];
    }

    /**
     * Get municipios from database for select options.
     * Cached for 24 hours (data rarely changes).
     */
    protected static function getMunicipiosOptions(): array
    {
        return Cache::remember('decretacoes.filter.municipios', 86400, function () {
            return Municipio::query()
                ->select('id', 'nome', 'codigo_ibge')
                ->orderBy('nome')
                ->get()
                ->map(fn ($m) => [
                    'id' => $m->id,
                    'label' => $m->nome,
                    'codigo_ibge' => $m->codigo_ibge,
                ])
                ->toArray();
        });
    }

    /**
     * Get analistas from database for select options.
     * Cached for 1 hour.
     */
    protected static function getAnalistasOptions(): array
    {
        return Cache::remember('decretacoes.filter.analistas', 3600, function () {
            return Processo::query()
                ->distinct()
                ->whereNotNull('analista')
                ->where('analista', '!=', '')
                ->pluck('analista')
                ->sort()
                ->values()
                ->toArray();
        });
    }

    /**
     * Build hierarchical COBRADE structure for tree view.
     */
    protected static function buildCobradeHierarchy($tiposDesastre): array
    {
        $hierarchy = [];

        foreach ($tiposDesastre as $item) {
            $grupo = $item['grupo'] ?? 'Outros';
            $subgrupo = $item['subgrupo'] ?? 'Geral';

            if (!isset($hierarchy[$grupo])) {
                $hierarchy[$grupo] = [
                    'label' => $grupo,
                    'subgrupos' => [],
                    'ids' => [],
                ];
            }

            if (!isset($hierarchy[$grupo]['subgrupos'][$subgrupo])) {
                $hierarchy[$grupo]['subgrupos'][$subgrupo] = [
                    'label' => $subgrupo,
                    'items' => [],
                    'ids' => [],
                ];
            }

            $label = $item['a_definicao']
                ?? $item['subtipo']
                ?? $item['tipo']
                ?? $subgrupo;

            $hierarchy[$grupo]['subgrupos'][$subgrupo]['items'][] = [
                'id' => $item['id'],
                'cobrade' => $item['cobrade'] ?? null,
                'label' => $label,
                'tipo' => $item['tipo'] ?? null,
                'subtipo' => $item['subtipo'] ?? null,
            ];

            $hierarchy[$grupo]['subgrupos'][$subgrupo]['ids'][] = $item['id'];
            $hierarchy[$grupo]['ids'][] = $item['id'];
        }

        // Convert to indexed arrays and sort
        $result = [];
        foreach ($hierarchy as $grupoKey => $grupo) {
            $subgrupos = [];
            foreach ($grupo['subgrupos'] as $subgrupoKey => $subgrupo) {
                $subgrupos[] = [
                    'label' => $subgrupo['label'],
                    'items' => $subgrupo['items'],
                    'ids' => array_unique($subgrupo['ids']),
                ];
            }

            $result[] = [
                'label' => $grupo['label'],
                'subgrupos' => $subgrupos,
                'ids' => array_unique($grupo['ids']),
            ];
        }

        return $result;
    }

    /**
     * Get predefined quick filter mappings for COBRADE categories.
     */
    protected static function getCobradeQuickFilters(): array
    {
        return [
            [
                'key' => 'BIOLOGICO',
                'label' => 'Biologico',
                'ids' => [34, 35, 36, 37, 38, 39, 40, 41],
            ],
            [
                'key' => 'CHUVA',
                'label' => 'Chuva',
                'ids' => [4, 5, 6, 7, 8, 9, 10, 11, 14, 15, 16, 17, 21, 22, 23, 24, 25, 42],
            ],
            [
                'key' => 'OUTROS',
                'label' => 'Outros',
                'ids' => [1, 2, 3, 12, 13, 18, 19, 20, 26, 27, 28, 33, 43],
            ],
            [
                'key' => 'SECA',
                'label' => 'Seca',
                'ids' => [29, 30, 31, 32],
            ],
            [
                'key' => 'TECNOLOGICO',
                'label' => 'Tecnologico',
                'ids' => [44, 45, 46, 47, 48, 49, 50, 51, 52, 53, 54, 55, 56, 57, 58, 59, 60, 61, 62, 63, 64, 65],
            ],
        ];
    }
}
