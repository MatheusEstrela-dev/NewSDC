<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Services;

use App\Modules\Decretacoes\Services\RedecService;
use App\Modules\Treinamento\DTOs\IndicadoresFiltroDTO;
use Carbon\CarbonImmutable;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

/**
 * Consulta de UMA requisicao ao painel de indicadores.
 *
 * Os fatos (turma x mes x sexo -> total) saem do banco uma unica vez, na
 * primeira vez que um bloco precisa deles; todos os blocos derivam disso em
 * memoria. O memo mora neste objeto, criado por requisicao pelo controller: o
 * service e singleton no Octane e nao pode guardar estado entre requisicoes.
 */
final class IndicadoresConsulta
{
    private const LIMITE_CAPACITACOES = 15;

    private const LIMITE_MUNICIPIOS = 20;

    private const POR_PAGINA = 20;

    /** @var Collection<int, array<string, mixed>>|null */
    private ?Collection $fatos = null;

    /** @var Collection<int, array{municipio_id: int, participantes: int, capacitacoes: int}>|null */
    private ?Collection $municipios = null;

    /** @var array<int, array<string, mixed>>|null */
    private ?array $catalogo = null;

    public function __construct(
        private readonly IndicadoresFiltroDTO $filtro,
        private readonly IndicadoresService $service,
    ) {}

    /** @return array<string, mixed> */
    public function kpis(): array
    {
        $fatos = $this->fatos();
        $participantes = (int) $fatos->sum('total');
        $capacitacoes = $fatos->pluck('treinamento_id')->unique()->count();
        $sexo = $this->porSexo();

        $catalogo = $this->catalogo();
        $municipiosIds = $this->porMunicipio()->pluck('municipio_id');
        $municipiosAtendidos = $municipiosIds->count();
        $redecsAtendidas = $municipiosIds
            ->map(fn (int $id) => $catalogo[$id]['redec_id'] ?? null)
            ->filter()
            ->unique()
            ->count();
        $populacao = $this->populacaoDoRecorte($catalogo);

        return [
            'total_capacitacoes' => $capacitacoes,
            'total_participantes' => $participantes,
            'municipios_atendidos' => $municipiosAtendidos,
            'redecs_atendidas' => $redecsAtendidas,
            'participacao_feminina_pct' => $this->percentual($sexo['feminino'], $participantes),
            'participacao_masculina_pct' => $this->percentual($sexo['masculino'], $participantes),
            'sexo_nao_informado' => $sexo['nao_informado'],
            'capacitados_por_100k' => $populacao > 0 ? round($participantes / $populacao * 100000, 1) : null,
            'pct_municipios_mg' => $this->percentual($municipiosAtendidos, count($catalogo)),
            'pct_redecs' => $this->percentual($redecsAtendidas, count(RedecService::all())),
            'media_participantes_por_capacitacao' => $capacitacoes > 0 ? round($participantes / $capacitacoes, 1) : 0.0,
        ];
    }

    /** @return list<array{municipio_id: int, nome: string, lat: float, lng: float, participantes: int, capacitacoes: int}> */
    public function mapa(): array
    {
        $catalogo = $this->catalogo();

        // Sem coordenada nao ha ponto no mapa; o municipio segue no ranking.
        return $this->porMunicipio()
            ->filter(fn ($l) => isset($catalogo[$l['municipio_id']]) && $catalogo[$l['municipio_id']]['lat'] !== null && $catalogo[$l['municipio_id']]['lng'] !== null)
            ->map(fn ($l) => [
                'municipio_id' => $l['municipio_id'],
                'nome' => $catalogo[$l['municipio_id']]['nome'],
                'lat' => $catalogo[$l['municipio_id']]['lat'],
                'lng' => $catalogo[$l['municipio_id']]['lng'],
                'participantes' => $l['participantes'],
                'capacitacoes' => $l['capacitacoes'],
            ])
            ->values()
            ->all();
    }

    /** @return array{participantes: int, capacitacoes: int} */
    public function semMunicipio(): array
    {
        $sem = $this->fatos()->whereNull('municipio_id');

        return [
            'participantes' => (int) $sem->sum('total'),
            'capacitacoes' => $sem->pluck('treinamento_id')->unique()->count(),
        ];
    }

    /** @return list<array{redec_id: int, nome: string, participantes: int, capacitacoes: int}> */
    public function porRedec(): array
    {
        $catalogo = $this->catalogo();

        return $this->porMunicipio()
            ->filter(fn ($l) => ($catalogo[$l['municipio_id']]['redec_id'] ?? null) !== null)
            ->groupBy(fn ($l) => $catalogo[$l['municipio_id']]['redec_id'])
            ->map(fn (Collection $grupo, int $redecId) => [
                'redec_id' => $redecId,
                'nome' => $this->service->nomeDaRedec($redecId),
                // Uma turma tem um unico municipio: somar por municipio nao conta turma duas vezes.
                'participantes' => (int) $grupo->sum('participantes'),
                'capacitacoes' => (int) $grupo->sum('capacitacoes'),
            ])
            ->sortBy([['participantes', 'desc'], ['redec_id', 'asc']])
            ->values()
            ->all();
    }

    /** @return list<array{mes: string, participantes: int, feminino: int, masculino: int, nao_informado: int}> */
    public function porMes(): array
    {
        $porMes = $this->fatos()->groupBy('mes');

        return array_map(function (string $mes) use ($porMes) {
            $g = $porMes[$mes] ?? collect();

            return [
                'mes' => $mes,
                'participantes' => (int) $g->sum('total'),
                // Pilhas do grafico mensal: o mesmo recorte de porSexo(), mes a mes.
                'feminino' => (int) $g->where('sexo', 'F')->sum('total'),
                'masculino' => (int) $g->where('sexo', 'M')->sum('total'),
                'nao_informado' => (int) $g->whereNull('sexo')->sum('total'),
            ];
        }, $this->janelaDeMeses());
    }

    /** @return list<array{ano: int, capacitacoes: int, participantes: int}> */
    public function porAno(): array
    {
        return $this->service->porAno($this->filtro);
    }

    /** @return array{feminino: int, masculino: int, nao_informado: int} */
    public function porSexo(): array
    {
        $fatos = $this->fatos();

        return [
            'feminino' => (int) $fatos->where('sexo', 'F')->sum('total'),
            'masculino' => (int) $fatos->where('sexo', 'M')->sum('total'),
            'nao_informado' => (int) $fatos->whereNull('sexo')->sum('total'),
        ];
    }

    /** @return list<array{treinamento_id: int, titulo: string, participantes: int, serie: list<int>}> */
    public function porCapacitacao(): array
    {
        $janela = $this->janelaDeMeses();

        return $this->fatos()
            ->groupBy('treinamento_id')
            ->map(function (Collection $g, int $id) use ($janela) {
                $porMes = $g->groupBy('mes')->map(fn (Collection $m) => (int) $m->sum('total'));

                return [
                    'treinamento_id' => $id,
                    'titulo' => $g->first()['titulo'],
                    'participantes' => (int) $g->sum('total'),
                    // Mini grafico do card: mesma janela de porMes().
                    'serie' => array_map(fn (string $mes) => $porMes[$mes] ?? 0, $janela),
                ];
            })
            ->sortBy([['participantes', 'desc'], ['titulo', 'asc']])
            ->take(self::LIMITE_CAPACITACOES)
            ->values()
            ->all();
    }

    /** @return list<array{municipio_id: int, nome: string, redec: ?string, participantes: int, capacitacoes: int}> */
    public function rankingMunicipios(): array
    {
        $catalogo = $this->catalogo();

        return $this->porMunicipio()
            ->take(self::LIMITE_MUNICIPIOS)
            ->map(fn ($l) => [
                'municipio_id' => $l['municipio_id'],
                'nome' => $catalogo[$l['municipio_id']]['nome'] ?? "Municipio {$l['municipio_id']}",
                'redec' => $this->service->nomeDaRedec($catalogo[$l['municipio_id']]['redec_id'] ?? null),
                'participantes' => $l['participantes'],
                'capacitacoes' => $l['capacitacoes'],
            ])
            ->values()
            ->all();
    }

    /** Uma linha por turma, paginada em memoria (as linhas ja estao carregadas). */
    public function tabela(): LengthAwarePaginator
    {
        $linhas = $this->linhasDaTabela();
        $pagina = Paginator::resolveCurrentPage();

        return new LengthAwarePaginator(
            $linhas->forPage($pagina, self::POR_PAGINA)->values(),
            $linhas->count(),
            self::POR_PAGINA,
            $pagina,
            ['path' => Paginator::resolveCurrentPath()],
        );
    }

    /**
     * Tudo o que o relatorio (PDF e planilha) imprime, no mesmo recorte da tela.
     * A tabela vem inteira, sem paginar: o relatorio e o documento completo.
     *
     * @return array<string, mixed>
     */
    public function relatorio(): array
    {
        return [
            'gerado_em' => CarbonImmutable::now()->toIso8601String(),
            'kpis' => $this->kpis(),
            'porRedec' => $this->porRedec(),
            'porAno' => $this->porAno(),
            'porSexo' => $this->porSexo(),
            'rankingMunicipios' => $this->rankingMunicipios(),
            'semMunicipio' => $this->semMunicipio(),
            'linhas' => $this->linhasDaTabela()->all(),
        ];
    }

    /** @return array<string, mixed> */
    public function opcoes(): array
    {
        return $this->service->opcoes();
    }

    // ------------------------------------------------------------ internos

    /**
     * Os 12 meses ('Y-m') dos graficos mensais: o ano filtrado, ou os ultimos
     * 12 meses sem filtro de ano (nao o historico todo).
     *
     * @return list<string>
     */
    private function janelaDeMeses(): array
    {
        $fim = $this->filtro->ano !== null
            ? CarbonImmutable::create($this->filtro->ano, 12, 1)
            : CarbonImmutable::now()->startOfMonth();

        $meses = [];
        for ($mes = $fim->subMonths(11); $mes <= $fim; $mes = $mes->addMonth()) {
            $meses[] = $mes->format('Y-m');
        }

        return $meses;
    }

    /**
     * Linhas da tabela detalhada (uma por turma), da mais recente para a mais
     * antiga. Fonte unica da tela paginada e do relatorio.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function linhasDaTabela(): Collection
    {
        $catalogo = $this->catalogo();

        return $this->fatos()
            ->groupBy('treinamento_id')
            ->map(function (Collection $g) use ($catalogo) {
                $turma = $g->first();
                $municipio = $turma['municipio_id'] !== null ? ($catalogo[$turma['municipio_id']] ?? null) : null;

                return [
                    'municipio' => $municipio['nome'] ?? null,
                    'redec' => $this->service->nomeDaRedec($municipio['redec_id'] ?? null),
                    'mesorregiao' => $municipio['mesorregiao'] ?? null,
                    'treinamento' => $turma['titulo'],
                    'ano' => $turma['ano'],
                    'periodo_inicio' => $turma['data_inicio'],
                    'periodo_fim' => $turma['data_fim'],
                    'participantes' => (int) $g->sum('total'),
                    'masculino' => (int) $g->where('sexo', 'M')->sum('total'),
                    'feminino' => (int) $g->where('sexo', 'F')->sum('total'),
                    'treinamento_id' => $turma['treinamento_id'],
                ];
            })
            ->sort(fn ($a, $b) => [$b['ano'], $b['periodo_inicio'] ?? '', $a['treinamento'], $a['treinamento_id']]
                <=> [$a['ano'], $a['periodo_inicio'] ?? '', $b['treinamento'], $b['treinamento_id']])
            ->map(fn ($l) => array_diff_key($l, ['treinamento_id' => true]))
            ->values();
    }

    /** @return Collection<int, array<string, mixed>> */
    private function fatos(): Collection
    {
        return $this->fatos ??= $this->service->fatos($this->filtro);
    }

    /** @return array<int, array<string, mixed>> */
    private function catalogo(): array
    {
        return $this->catalogo ??= $this->service->catalogoMunicipios();
    }

    /**
     * Agregado por municipio da turma, do maior para o menor. Turma sem
     * municipio fica de fora: ela aparece a parte em semMunicipio().
     *
     * @return Collection<int, array{municipio_id: int, participantes: int, capacitacoes: int}>
     */
    private function porMunicipio(): Collection
    {
        return $this->municipios ??= $this->fatos()
            ->whereNotNull('municipio_id')
            ->groupBy('municipio_id')
            ->map(fn (Collection $g, int $id) => [
                'municipio_id' => $id,
                'participantes' => (int) $g->sum('total'),
                'capacitacoes' => $g->pluck('treinamento_id')->unique()->count(),
            ])
            ->sortBy([['participantes', 'desc'], ['municipio_id', 'asc']])
            ->values();
    }

    /**
     * Populacao do recorte: o municipio ou a REDEC filtrados, ou o estado todo.
     * O indicador por 100 mil so faz sentido contra a populacao de onde se mede.
     *
     * @param  array<int, array<string, mixed>>  $catalogo
     */
    private function populacaoDoRecorte(array $catalogo): float
    {
        return (float) collect($catalogo)
            ->filter(fn ($m, $id) => ($this->filtro->municipioId === null || $id === $this->filtro->municipioId)
                && ($this->filtro->redecId === null || $m['redec_id'] === $this->filtro->redecId))
            ->sum('populacao');
    }

    private function percentual(int $parte, int $total): float
    {
        return $total > 0 ? round($parte / $total * 100, 1) : 0.0;
    }
}
