<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes\Filters;

use App\Models\Municipio;
use App\Modules\Decretacoes\Enums\Redec;
use App\Modules\Decretacoes\Enums\StatusProcesso;
use App\Modules\Decretacoes\Models\DecretoMunicipio;
use App\Modules\Decretacoes\Models\Processo;
use App\Modules\Decretacoes\Support\Vigencia;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProcessoFilter
{
    /** O modulo atende exclusivamente municipios de Minas Gerais. */
    private const UF_MINAS_GERAIS = 'MG';

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
             ->filterByRedec()
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

    /**
     * Indica exportacao de toda a serie historica (ignora recortes de data).
     */
    protected function exportandoSerieCompleta(): bool
    {
        return filter_var($this->request->input('all', false), FILTER_VALIDATE_BOOLEAN)
            || $this->request->input('type') === 'all';
    }

    protected function filterByDataEntrada(): self
    {
        if ($this->exportandoSerieCompleta()) {
            return $this;
        }

        if ($this->request->filled('data_entrada')) {
            $this->builder->whereDate('data_entrada', $this->request->input('data_entrada'));
        }

        return $this;
    }

    /**
     * Filtra pelo intervalo de data de entrada.
     *
     * EXPORTACAO: quando o modal escolhe "Toda Serie Historica" (`all=1` ou
     * `type=all`), o recorte de datas e ignorado de proposito - os demais
     * filtros da tela (REDEC, municipio, vigencia, COBRADE...) continuam
     * valendo, para o CSV sair "de acordo" com o que esta na listagem.
     */
    protected function filterByDateRange(): self
    {
        if ($this->exportandoSerieCompleta()) {
            return $this;
        }

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

    /**
     * Filtra por status do processo.
     *
     * O status vive em duas colunas: `reconhecimento` (legado, preenchido pela
     * carga antiga) e `status` (gravado pelo formulario atual). Filtrar apenas
     * `reconhecimento` deixava os processos novos invisiveis para este filtro,
     * por isso a comparacao usa o status efetivo (o primeiro nao vazio).
     */
    protected function filterByReconhecimento(): self
    {
        if ($this->request->filled('reconhecimento')) {
            $reconhecimentos = array_values(array_filter(
                array_map('trim', explode(',', (string) $this->request->input('reconhecimento')))
            ));

            if (! empty($reconhecimentos)) {
                $this->builder->whereIn(DB::raw(self::sqlStatusEfetivo()), $reconhecimentos);
            }
        }

        return $this;
    }

    /**
     * Expressao SQL do status efetivo do processo (legado ou atual).
     */
    public static function sqlStatusEfetivo(string $tabela = ''): string
    {
        $prefixo = $tabela !== '' ? "{$tabela}." : '';

        return "COALESCE(NULLIF(TRIM({$prefixo}reconhecimento), ''), {$prefixo}status)";
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

    /**
     * Filtra por status de vigencia.
     *
     * Usa a expressao canonica de Support\Vigencia (com COALESCE de 180 dias),
     * para que registros sem `prazo_vigencia` informado nao desapareguem dos
     * recortes de vigente/vencido.
     */
    protected function filterByVigenciaStatus(): self
    {
        if ($this->request->filled('vigencia_status')) {
            $vigenciaStatus = $this->request->input('vigencia_status');
            $vencimento = Vigencia::sqlVencimento();
            $janela = Vigencia::JANELA_PROXIMO_VENCER_DIAS;

            switch ($vigenciaStatus) {
                case 'vigente':
                    $this->builder->where(function ($q) use ($vencimento) {
                        $q->whereNull('data_publicacao_mg')
                          ->orWhereRaw("{$vencimento} >= CURRENT_DATE");
                    });
                    break;

                case 'vencido':
                    $this->builder->whereNotNull('data_publicacao_mg')
                                 ->whereRaw("{$vencimento} < CURRENT_DATE");
                    break;

                case 'proximo_vencer':
                    $this->builder->whereNotNull('data_publicacao_mg')
                                 ->whereRaw("({$vencimento} - CURRENT_DATE) BETWEEN 0 AND {$janela}");
                    break;

                case 'sem_data':
                    $this->builder->whereNull('data_publicacao_mg');
                    break;
            }
        }

        return $this;
    }

    /**
     * Filtra por REDEC do processo.
     *
     * Alem do `redec_id` gravado no processo, aceita processos cujos municipios
     * pertencam a REDEC (correspondencia via cedec_municipio), cobrindo os
     * registros legados que nao tiveram a REDEC preenchida.
     */
    protected function filterByRedec(): self
    {
        if (! $this->request->filled('redec_id')) {
            return $this;
        }

        $redecId = (int) $this->request->input('redec_id');
        $table = $this->builder->getModel()->getTable();
        $municipioIds = self::getMunicipioIdsPorRedec($redecId);

        $this->builder->where(function ($q) use ($redecId, $municipioIds, $table) {
            $q->where('redec_id', $redecId);

            if (! empty($municipioIds)) {
                $q->orWhereExists(function ($sub) use ($municipioIds, $table) {
                    $sub->select(DB::raw(1))
                        ->from('dec_decreto_municipios as dm')
                        ->whereColumn('dm.entrada_processos_id', "{$table}.id")
                        ->whereNull('dm.deleted_at')
                        ->whereIn('dm.municipio_id', $municipioIds);
                });
            }
        });

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
            'reconhecimentos' => self::getStatusProcessoOptions(),
            'municipios' => self::getMunicipiosOptions(),
            'redecs' => Redec::toSelectOptions(),
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
            // Lista por nome do desastre (select "Tipo de Desastre")
            'tipos_desastre' => $tiposDesastre
                ->map(fn ($item) => self::mapDesastreOption($item))
                ->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)
                ->values(),
            // Lista numerada pelo codigo COBRADE (select "COBRADE")
            'cobrades' => self::buildCobradeOptions($tiposDesastre),
            'tipos_desastre_hierarquico' => self::buildCobradeHierarchy($tiposDesastre),
            'cobrade_quick_filters' => self::getCobradeQuickFilters(),
        ];
    }

    /**
     * Get municipios from database for select options.
     * Cached for 24 hours (data rarely changes).
     *
     * Cada municipio carrega a REDEC a que pertence (`redec_id`/`redec_sigla`),
     * o que permite as listas suspensas do modulo filtrarem municipio <-> REDEC
     * sem uma segunda requisicao.
     *
     * FONTE DA CORRESPONDENCIA: cedec_municipio.redec_id, ligado a municipios
     * por LEFT(cedec_municipio."Codmundv", 7) = municipios.codigo_ibge
     * (populado por `php artisan legado:importar-cedec-municipio`).
     */
    protected static function getMunicipiosOptions(): array
    {
        return Cache::remember('decretacoes.filter.municipios.v4', 86400, function () {
            $redecPorMunicipio = self::getRedecPorMunicipioId();

            // Os 853 municipios de Minas Gerais, sempre completos: a REDEC
            // apenas anota/ordena a lista, nunca remove opcao (ver ProcessoForm).
            //
            // A ordenacao e feita em PHP sobre o nome sem acentos: o Postgres
            // desta instalacao usa colacao por byte, que joga "Aguas Vermelhas"
            // e "Varzea da Palma" para fora de ordem numa lista tao longa.
            return Municipio::query()
                ->select('id', 'nome', 'codigo_ibge')
                ->where('uf', self::UF_MINAS_GERAIS)
                ->get()
                ->map(function ($m) use ($redecPorMunicipio) {
                    $redecId = $redecPorMunicipio[(int) $m->id] ?? null;
                    $redec = $redecId ? Redec::tryFrom($redecId) : null;

                    return [
                        'id' => $m->id,
                        'label' => $m->nome,
                        'codigo_ibge' => $m->codigo_ibge,
                        'redec_id' => $redecId,
                        'redec_sigla' => $redec?->sigla(),
                        'redec_label' => $redec?->label(),
                    ];
                })
                ->sortBy(fn (array $m) => Str::lower(Str::ascii($m['label'])), SORT_NATURAL)
                ->values()
                ->toArray();
        });
    }

    /**
     * Correspondencia municipio -> REDEC usada por todas as listas suspensas.
     *
     * Combina duas fontes reais, na ordem de confianca:
     *   1. cedec_municipio.redec_id (cadastro oficial da CEDEC), ligado a
     *      `municipios` por LEFT("Codmundv", 7) = codigo_ibge. Requer
     *      `php artisan legado:importar-cedec-municipio`.
     *   2. Historico do proprio modulo: a REDEC mais frequente entre os
     *      processos que ja incluiram aquele municipio. Cobre as bases em que o
     *      dump da CEDEC nao foi importado, sem inventar vinculo nenhum.
     *
     * Municipio sem nenhuma das duas fontes fica sem REDEC e as listas
     * simplesmente nao aplicam o recorte para ele.
     *
     * @return array<int, int> municipio_id => redec_id
     */
    public static function getRedecPorMunicipioId(): array
    {
        return Cache::remember('decretacoes.filter.redec_por_municipio.v1', 86400, function () {
            $porIbge = self::getRedecPorIbge();
            $mapa = [];

            if (! empty($porIbge)) {
                $mapa = Municipio::query()
                    ->where('uf', self::UF_MINAS_GERAIS)
                    ->whereIn('codigo_ibge', array_keys($porIbge))
                    ->pluck('codigo_ibge', 'id')
                    ->mapWithKeys(fn ($ibge, $id) => [(int) $id => $porIbge[$ibge]])
                    ->all();
            }

            // Fallback historico apenas para os municipios ainda sem REDEC.
            foreach (self::getRedecHistoricoPorMunicipioId() as $municipioId => $redecId) {
                if (! isset($mapa[$municipioId])) {
                    $mapa[$municipioId] = $redecId;
                }
            }

            return $mapa;
        });
    }

    /**
     * Mapa codigo_ibge => redec_id a partir do cadastro legado cedec_municipio.
     *
     * @return array<string, int>
     */
    protected static function getRedecPorIbge(): array
    {
        try {
            return DB::table('cedec_municipio')
                ->whereNotNull('redec_id')
                ->whereNotNull('Codmundv')
                ->selectRaw('LEFT("Codmundv", 7) as codigo_ibge, MIN(redec_id) as redec_id')
                ->groupByRaw('LEFT("Codmundv", 7)')
                ->pluck('redec_id', 'codigo_ibge')
                ->map(fn ($id) => (int) $id)
                ->all();
        } catch (\Throwable) {
            // cedec_municipio ausente/nao importado: cai no fallback historico.
            return [];
        }
    }

    /**
     * REDEC predominante de cada municipio segundo o historico de processos.
     *
     * Para cada municipio ja vinculado a processos, elege a REDEC com mais
     * ocorrencias (empate resolvido pelo menor id de REDEC, deterministico).
     *
     * @return array<int, int> municipio_id => redec_id
     */
    protected static function getRedecHistoricoPorMunicipioId(): array
    {
        try {
            $rows = DB::table('dec_decreto_municipios as dm')
                ->join('dec_entrada_processos as p', 'p.id', '=', 'dm.entrada_processos_id')
                ->whereNull('dm.deleted_at')
                ->whereNull('p.deleted_at')
                ->whereNotNull('p.redec_id')
                ->selectRaw('dm.municipio_id, p.redec_id, COUNT(*) as total')
                ->groupBy('dm.municipio_id', 'p.redec_id')
                ->orderBy('dm.municipio_id')
                ->orderByDesc('total')
                ->orderBy('p.redec_id')
                ->get();

            $mapa = [];
            foreach ($rows as $row) {
                $municipioId = (int) $row->municipio_id;

                // A primeira linha de cada municipio ja e a REDEC predominante.
                if (! isset($mapa[$municipioId])) {
                    $mapa[$municipioId] = (int) $row->redec_id;
                }
            }

            return $mapa;
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * IDs de municipios (tabela `municipios`) pertencentes a uma REDEC.
     *
     * @return array<int, int>
     */
    public static function getMunicipioIdsPorRedec(int $redecId): array
    {
        return array_keys(array_filter(
            self::getRedecPorMunicipioId(),
            fn ($id) => $id === $redecId
        ));
    }

    /**
     * Opcoes do filtro "Status do Processo".
     *
     * Lista os status VIGENTES primeiro - os do enum StatusProcesso, que e a
     * unica lista valida para novos processos - e so depois os valores legados
     * que ainda existem no banco e nao pertencem ao enum (marcados como
     * "legado"), para que os registros antigos continuem filtraveis.
     *
     * O status considerado e o efetivo (`reconhecimento` legado ou `status`
     * atual), o mesmo usado por filterByReconhecimento().
     *
     * @return array<int, array{value: string, label: string, vigente: bool}>
     */
    protected static function getStatusProcessoOptions(): array
    {
        return Cache::remember('decretacoes.filter.status_processo.v1', 3600, function () {
            $vigentes = array_map(
                fn (StatusProcesso $case) => ['value' => $case->value, 'label' => $case->value, 'vigente' => true],
                StatusProcesso::cases()
            );

            $valoresVigentes = array_column($vigentes, 'value');

            try {
                $legados = Processo::query()
                    ->selectRaw(self::sqlStatusEfetivo() . ' as status_efetivo')
                    ->distinct()
                    ->pluck('status_efetivo')
                    ->filter(fn ($status) => is_string($status) && trim($status) !== '')
                    ->map(fn ($status) => trim($status))
                    ->reject(fn ($status) => in_array($status, $valoresVigentes, true))
                    ->unique()
                    ->sort()
                    ->map(fn ($status) => ['value' => $status, 'label' => $status . ' (legado)', 'vigente' => false])
                    ->values()
                    ->all();
            } catch (\Throwable) {
                $legados = [];
            }

            return array_merge($vigentes, $legados);
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
     * Nome do desastre segundo a classificacao (do mais especifico ao mais geral).
     *
     * @param array<string, mixed> $item Linha de app/Enums/classificacao_desastres.php
     */
    protected static function desastreNome(array $item): string
    {
        return $item['a_definicao']
            ?? $item['subtipo']
            ?? $item['tipo']
            ?? $item['subgrupo']
            ?? $item['grupo']
            ?? $item['categoria']
            ?? 'N/A';
    }

    /**
     * Opcao de desastre no formato consumido pelos selects.
     *
     * `label`         -> nome do desastre
     * `label_cobrade` -> numerado no padrao nacional ("1.1.1.1.0 - Tremor de terra.")
     *
     * @param array<string, mixed> $item
     * @return array<string, mixed>
     */
    protected static function mapDesastreOption(array $item): array
    {
        $nome = self::desastreNome($item);
        $codigo = $item['cobrade'] ?? null;

        return [
            'id' => $item['id'],
            'cobrade' => $codigo,
            'label' => $nome,
            'label_cobrade' => $codigo ? "{$codigo} - {$nome}" : $nome,
            'grupo' => $item['grupo'] ?? null,
            'subgrupo' => $item['subgrupo'] ?? null,
            'tipo' => $item['tipo'] ?? null,
            'subtipo' => $item['subtipo'] ?? null,
        ];
    }

    /**
     * Opcoes da lista suspensa de COBRADE, numeradas pelo padrao nacional.
     *
     * O rotulo exibido e o proprio codigo COBRADE seguido do nome do desastre
     * ("1.1.3.1.1 - Queda, tombamento ou rolamento de blocos em encostas.") e a
     * ordem segue a hierarquia do codigo (grupo > subgrupo > tipo > subtipo),
     * nao a ordem alfabetica - e assim que a classificacao e publicada.
     *
     * @param Collection $tiposDesastre Linhas de classificacao_desastres.php
     * @return array<int, array<string, mixed>>
     */
    protected static function buildCobradeOptions(Collection $tiposDesastre): array
    {
        return $tiposDesastre
            ->map(function ($item) {
                $opcao = self::mapDesastreOption($item);

                // No select de COBRADE o rotulo ja vem numerado.
                $opcao['label'] = $opcao['label_cobrade'];
                $opcao['descricao'] = self::desastreNome($item);

                return $opcao;
            })
            ->sortBy(fn (array $opcao) => self::cobradeSortKey($opcao['cobrade']))
            ->values()
            ->toArray();
    }

    /**
     * Chave de ordenacao hierarquica de um codigo COBRADE.
     *
     * Cada segmento e normalizado com dois digitos para que a comparacao seja
     * numerica ("1.1.10.0.0" depois de "1.1.9.0.0", e nao antes).
     */
    protected static function cobradeSortKey(?string $cobrade): string
    {
        if (! $cobrade) {
            return 'zz';
        }

        $segmentos = array_map(
            fn (string $parte) => str_pad(preg_replace('/\D/', '', $parte) ?: '0', 2, '0', STR_PAD_LEFT),
            explode('.', $cobrade)
        );

        return implode('.', $segmentos);
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

            $codigo = $item['cobrade'] ?? null;

            $hierarchy[$grupo]['subgrupos'][$subgrupo]['items'][] = [
                'id' => $item['id'],
                'cobrade' => $codigo,
                'label' => $label,
                // Rotulo numerado no padrao nacional, para a arvore de filtro
                'label_cobrade' => $codigo ? "{$codigo} - {$label}" : $label,
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
