<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes\Filters;

use App\Modules\Decretacoes\Models\DecretoMunicipio;
use App\Modules\Decretacoes\Models\Processo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
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
            $this->builder->where(function ($q) use ($search) {
                $q->where('analista', 'like', "%$search%")
                  ->orWhere('n_protocolo_fide', 'like', "%$search%")
                  ->orWhereHas('municipios', function ($q2) use ($search) {
                      $q2->where('cedec_municipio.p_nome', 'like', "%$search%")
                         ->orWhere('cedec_municipio.nome', 'like', "%$search%")
                         ->orWhere('cedec_municipio.id', 'like', "%$search%");
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
        if ($this->request->filled('data_entrada_inicio')) {
            $this->builder->whereDate('data_entrada', '>=', $this->request->input('data_entrada_inicio'));
        }

        if ($this->request->filled('data_entrada_fim')) {
            $this->builder->whereDate('data_entrada', '<=', $this->request->input('data_entrada_fim'));
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
                    $this->builder->where(function ($q) {
                        $q->whereNull('data_publicacao_mg')
                          ->orWhereRaw('DATE_ADD(data_publicacao_mg, INTERVAL prazo_vigencia DAY) >= CURDATE()');
                    });
                    break;

                case 'vencido':
                    $this->builder->whereNotNull('data_publicacao_mg')
                                 ->whereRaw('DATE_ADD(data_publicacao_mg, INTERVAL prazo_vigencia DAY) < CURDATE()');
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
            $this->builder->whereHas('municipios', function ($q) {
                $q->where('cedec_municipio.id', $this->request->input('municipio_id'));
            });
        }

        return $this;
    }

    protected function filterByProtocoloFide(): self
    {
        if ($this->request->filled('n_protocolo_fide')) {
            $this->builder->whereHas('decretoMunicipios', function ($q) {
                $q->where('n_protocolo_fide', 'like', $this->request->input('n_protocolo_fide') . '%');
            })
            ->orWhere('n_protocolo_fide', 'like', $this->request->input('n_protocolo_fide') . '%');
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
        return [
            'analistas' => Processo::distinct('analista')
                ->whereNotNull('analista')
                ->where('analista', '!=', '')
                ->pluck('analista')
                ->sort()
                ->values(),
            'reconhecimentos' => Processo::distinct('reconhecimento')
                ->whereNotNull('reconhecimento')
                ->where('reconhecimento', '!=', '')
                ->pluck('reconhecimento')
                ->sort()
                ->values(),
            'municipios' => DecretoMunicipio::select('cedec_municipio.p_nome', DB::raw('count(dec_decreto_municipios.municipio_id) as total'), 'cedec_municipio.id')
                ->groupBy('dec_decreto_municipios.municipio_id')
                ->join('cedec_municipio', 'cedec_municipio.id', '=', 'dec_decreto_municipios.municipio_id')
                ->join('dec_entrada_processos', 'dec_entrada_processos.id', '=', 'dec_decreto_municipios.entrada_processos_id')
                ->whereNull('dec_entrada_processos.deleted_at')
                ->get()
                ->values(),
            'tipos_desastre' => collect(include app_path('Enums/classificacao_desastres.php'))
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
                    ];
                })
                ->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)
                ->values(),
        ];
    }
}
