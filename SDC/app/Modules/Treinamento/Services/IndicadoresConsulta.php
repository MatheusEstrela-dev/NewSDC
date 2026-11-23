<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Services;

use App\Modules\Decretacoes\Services\RedecService;
use App\Modules\Treinamento\DTOs\IndicadoresFiltroDTO;
use App\Modules\Treinamento\Enums\NotaAvaliacaoCurso;
use App\Modules\Treinamento\Enums\Sexo;
use App\Modules\Treinamento\Enums\SituacaoParticipacao;
use App\Modules\Treinamento\Support\FaixaEtaria;
use Carbon\CarbonImmutable;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

/**
 * Consulta de UMA requisicao ao painel de indicadores.
 *
 * Os fatos (turma x municipio x situacao x mes x sexo x faixa -> total) saem
 * do banco uma unica vez, na primeira vez que um bloco precisa deles; todos os
 * blocos derivam disso em memoria. Pessoas distintas e satisfacao tem consulta
 * propria, tambem memorizada. O memo mora neste objeto, criado por requisicao
 * pelo controller: o service e singleton no Octane e nao pode guardar estado
 * entre requisicoes.
 *
 * "Participantes" e o total de participacoes (situacao, mes e municipio de
 * referencia: Support\RegrasDaParticipacao); concluidos, em_andamento e
 * desistentes sao a particao desse total.
 */
final class IndicadoresConsulta
{
    private const LIMITE_CURSOS = 15;

    private const LIMITE_MUNICIPIOS = 20;

    /** Tabela compacta: o painel e de leitura rapida; o relatorio traz tudo. */
    private const POR_PAGINA = 6;

    private const SEM_CURSO = 'Sem curso no catálogo';

    /** Chave do painel => letra gravada em inscricoes.sexo; NULL = nao informado. */
    private const SEXOS = [
        'feminino' => Sexo::FEMININO->value,
        'masculino' => Sexo::MASCULINO->value,
        'nao_binario' => Sexo::NAO_BINARIO->value,
        'outro' => Sexo::OUTRO->value,
    ];

    /** @var Collection<int, array<string, mixed>>|null */
    private ?Collection $fatos = null;

    /** @var Collection<int, array<string, mixed>>|null */
    private ?Collection $municipios = null;

    /** @var array<int, array<string, mixed>>|null */
    private ?array $catalogo = null;

    /** @var Collection<int, array<string, mixed>>|null */
    private ?Collection $pessoas = null;

    /** @var array<string, mixed>|null */
    private ?array $satisfacao = null;

    /** @var Collection<int, array<string, mixed>>|null */
    private ?Collection $avaliacoes = null;

    public function __construct(
        private readonly IndicadoresFiltroDTO $filtro,
        private readonly IndicadoresService $service,
    ) {}

    /** @return array<string, mixed> */
    public function kpis(): array
    {
        $fatos = $this->fatos();
        $contagens = $this->contagens($fatos);
        $participantes = $contagens['participantes'];
        $capacitacoes = $fatos->pluck('treinamento_id')->unique()->count();
        $sexo = $this->porSexo();

        $catalogo = $this->catalogo();
        $municipiosIds = $this->porMunicipio()->pluck('municipio_id')->filter(fn (int $id) => isset($catalogo[$id]));
        $municipiosAtendidos = $municipiosIds->count();
        $redecsAtendidas = $municipiosIds
            ->map(fn (int $id) => $catalogo[$id]['redec_id'])
            ->filter()
            ->unique()
            ->count();
        $redecsTotal = count(RedecService::all());
        $populacao = $this->populacaoDoRecorte($catalogo);

        return [
            'total_participantes' => $participantes,
            'concluidos' => $contagens['concluidos'],
            'em_andamento' => $contagens['em_andamento'],
            'desistentes' => $contagens['desistentes'],
            'taxa_desistencia_pct' => $this->percentual($contagens['desistentes'], $participantes),
            'total_cursos' => $fatos->map(fn ($f) => self::chaveDoCurso($f))->unique()->count(),
            'total_capacitacoes' => $capacitacoes,
            'municipios_atendidos' => $municipiosAtendidos,
            'municipios_total' => count($catalogo),
            'pct_municipios_mg' => $this->percentual($municipiosAtendidos, count($catalogo)),
            'redecs_atendidas' => $redecsAtendidas,
            'redecs_total' => $redecsTotal,
            'pct_redecs' => $this->percentual($redecsAtendidas, $redecsTotal),
            'participacao_feminina_pct' => $this->percentual($sexo['feminino'], $participantes),
            'participacao_masculina_pct' => $this->percentual($sexo['masculino'], $participantes),
            'sexo_nao_informado' => $sexo['nao_informado'],
            // Capacitado = concluiu no prazo; a populacao e a do recorte territorial.
            'capacitados_por_100k' => $populacao > 0 ? round($contagens['concluidos'] / $populacao * 100000, 1) : null,
            'media_participantes_por_capacitacao' => $capacitacoes > 0 ? round($participantes / $capacitacoes, 1) : 0.0,
        ];
    }

    /**
     * Municipios (de referencia) com alguma participacao, com o que o mapa
     * precisa para as tres camadas. pessoas_* contam gente distinta naquela
     * situacao: a mesma pessoa pode estar nas duas (concluiu um curso e faz
     * outro). Municipio fora do catalogo de MG fica de fora (nao tem poligono).
     *
     * @return list<array<string, mixed>>
     */
    public function mapa(): array
    {
        $catalogo = $this->catalogo();
        $pessoas = $this->pessoas()->whereNotNull('municipio_referencia_id')->groupBy('municipio_referencia_id');
        $distintas = fn (int $id, SituacaoParticipacao $s) => ($pessoas[$id] ?? collect())
            ->where('situacao', $s->value)->pluck('pessoa')->unique()->count();

        return $this->porMunicipio()
            ->filter(fn ($l) => isset($catalogo[$l['municipio_id']]))
            ->map(function ($l) use ($catalogo, $distintas) {
                $m = $catalogo[$l['municipio_id']];

                return [
                    'municipio_id' => $l['municipio_id'],
                    'ibge' => $m['ibge'],
                    'nome' => $m['nome'],
                    'redec_id' => $m['redec_id'],
                    'lat' => $m['lat'],
                    'lng' => $m['lng'],
                    'participantes' => $l['participantes'],
                    'concluidos' => $l['concluidos'],
                    'em_andamento' => $l['em_andamento'],
                    'desistentes' => $l['desistentes'],
                    'pessoas_concluidas' => $distintas($l['municipio_id'], SituacaoParticipacao::CONCLUIDO),
                    'pessoas_em_andamento' => $distintas($l['municipio_id'], SituacaoParticipacao::EM_ANDAMENTO),
                    'capacitacoes' => $l['capacitacoes'],
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Todos os municipios de MG do catalogo, para pintar o mapa inteiro (os
     * vazios tambem). Nao depende de filtro.
     *
     * @return list<array{municipio_id: int, ibge: string, nome: string, redec_id: ?int}>
     */
    public function territorio(): array
    {
        return collect($this->catalogo())
            ->map(fn ($m, int $id) => [
                'municipio_id' => $id,
                'ibge' => $m['ibge'],
                'nome' => $m['nome'],
                'redec_id' => $m['redec_id'],
            ])
            ->sortBy('nome')
            ->values()
            ->all();
    }

    /** @return array{participantes: int, capacitacoes: int} */
    public function semMunicipio(): array
    {
        // Sem municipio OU com municipio fora do catalogo de MG (pessoa de outra UF):
        // nenhum dos dois tem poligono no mapa, e assim a soma com o mapa fecha o total.
        $catalogo = $this->catalogo();
        $sem = $this->fatos()->filter(fn ($f) => $f['municipio_id'] === null || ! isset($catalogo[$f['municipio_id']]));

        return [
            'participantes' => (int) $sem->sum('total'),
            'capacitacoes' => $sem->pluck('treinamento_id')->unique()->count(),
        ];
    }

    /**
     * Todas as REDECs (inclusive as zeradas), na ordem do id, com a cobertura
     * em municipios contra o total de municipios de cada uma.
     *
     * @return list<array{redec_id: int, nome: string, participantes: int, capacitacoes: int, municipios_atendidos: int, municipios_total: int}>
     */
    public function porRedec(): array
    {
        $catalogo = $this->catalogo();
        $totais = collect($catalogo)->whereNotNull('redec_id')->countBy('redec_id');
        $fatos = $this->fatos()
            ->filter(fn ($f) => $f['municipio_id'] !== null && ($catalogo[$f['municipio_id']]['redec_id'] ?? null) !== null)
            ->groupBy(fn ($f) => $catalogo[$f['municipio_id']]['redec_id']);

        return collect(RedecService::all())
            ->keys()
            ->sort()
            ->map(function (int $redecId) use ($fatos, $totais) {
                $g = $fatos[$redecId] ?? collect();

                return [
                    'redec_id' => $redecId,
                    'nome' => $this->service->nomeDaRedec($redecId),
                    'participantes' => (int) $g->sum('total'),
                    // Turmas distintas: uma turma EAD pode alcancar varios municipios da REDEC.
                    'capacitacoes' => $g->pluck('treinamento_id')->unique()->count(),
                    'municipios_atendidos' => $g->pluck('municipio_id')->unique()->count(),
                    'municipios_total' => (int) ($totais[$redecId] ?? 0),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Serie mensal pelo mes de referencia, sem sexo: a quantidade e a
     * particao por situacao.
     *
     * @return list<array{mes: string, participantes: int, concluidos: int, em_andamento: int, desistentes: int, capacitacoes: int}>
     */
    public function porMes(): array
    {
        $porMes = $this->fatos()->groupBy('mes');

        // capacitacoes = turmas distintas com participacao no mes (a linha do grafico combinado).
        return array_map(
            fn (string $mes) => ['mes' => $mes]
                + $this->contagens($porMes[$mes] ?? collect())
                + ['capacitacoes' => ($porMes[$mes] ?? collect())->pluck('treinamento_id')->unique()->count()],
            $this->janelaDeMeses()
        );
    }

    /**
     * Detalhe de um mes (clique no grafico mensal): os totais e as turmas que
     * tiveram participacao com mes de referencia nele.
     *
     * @param  string  $mes  'Y-m', ja validado pelo controller
     * @return array<string, mixed>
     */
    public function detalheMes(string $mes): array
    {
        $doMes = $this->fatos()->where('mes', $mes);

        $cursos = $doMes->groupBy('treinamento_id')
            ->map(function (Collection $g) {
                $turma = $g->first();

                return [
                    'treinamento_id' => $turma['treinamento_id'],
                    'titulo' => $turma['titulo'],
                    'curso' => $turma['curso'],
                    'versao' => $turma['versao'],
                    'data_inicio' => $turma['data_inicio'],
                    'data_fim' => $turma['data_fim'],
                    'prazo' => $turma['prazo'],
                ] + $this->contagens($g);
            })
            ->sort(fn ($a, $b) => [$b['participantes'], $a['titulo']] <=> [$a['participantes'], $b['titulo']])
            ->values()
            ->all();

        return ['mes' => $mes] + $this->contagens($doMes) + ['cursos' => $cursos];
    }

    /** @return list<array{ano: int, capacitacoes: int, participantes: int}> */
    public function porAno(): array
    {
        return $this->service->porAno($this->filtro);
    }

    /** @return array{feminino: int, masculino: int, nao_binario: int, outro: int, nao_informado: int} */
    public function porSexo(): array
    {
        $fatos = $this->fatos();

        $porSexo = array_map(fn (string $letra) => (int) $fatos->where('sexo', $letra)->sum('total'), self::SEXOS);
        $porSexo['nao_informado'] = (int) $fatos->whereNull('sexo')->sum('total');

        return $porSexo;
    }

    /**
     * Uma linha por faixa etaria, na ordem de FaixaEtaria (nao informado por
     * ultimo), faixas zeradas incluidas.
     *
     * @return list<array{faixa: string, rotulo: string, total: int}>
     */
    public function porFaixaEtaria(): array
    {
        $porFaixa = $this->fatos()->groupBy('faixa')->map(fn (Collection $g) => (int) $g->sum('total'));

        return array_map(
            fn (string $faixa, string $rotulo) => ['faixa' => $faixa, 'rotulo' => $rotulo, 'total' => (int) ($porFaixa[$faixa] ?? 0)],
            array_keys(FaixaEtaria::rotulos()),
            FaixaEtaria::rotulos()
        );
    }

    /**
     * Cursos do catalogo (turma sem curso vira uma linha propria), os de mais
     * participantes primeiro. O card filtra por curso_id ou, sem curso, pela
     * turma.
     *
     * @return list<array<string, mixed>>
     */
    public function porCurso(): array
    {
        $satisfacao = $this->satisfacaoPorCurso();

        return $this->fatos()
            ->groupBy(fn ($f) => self::chaveDoCurso($f))
            ->map(function (Collection $g, string $chave) use ($satisfacao) {
                $primeiro = $g->first();
                $contagens = $this->contagens($g);

                return [
                    'chave' => $chave,
                    'curso_id' => $primeiro['curso_id'],
                    'treinamento_id' => $primeiro['curso_id'] === null ? $primeiro['treinamento_id'] : null,
                    'nome' => self::nomeDoCurso($primeiro),
                    'turmas' => $g->pluck('treinamento_id')->unique()->count(),
                ] + $contagens + [
                    'taxa_desistencia_pct' => $this->percentual($contagens['desistentes'], $contagens['participantes']),
                    // Satisfacao do mesmo curso no card: le junto com volume e desistencia.
                    'satisfacao' => $satisfacao[$chave] ?? $this->resumoDeNotas(collect(), true),
                ];
            })
            ->sort(fn ($a, $b) => [$b['participantes'], $a['nome']] <=> [$a['participantes'], $b['nome']])
            ->take(self::LIMITE_CURSOS)
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

    /**
     * Pessoas distintas por curso do catalogo. Turma sem curso cai numa linha
     * propria. Municipios sao os da PESSOA, nao os da turma.
     *
     * @return list<array{curso_id: ?int, nome: string, pessoas: int, municipios: int, turmas: int}>
     */
    public function pessoasPorCurso(): array
    {
        return $this->pessoas()
            ->groupBy(fn ($l) => $l['curso_id'] ?? 0)
            ->map(fn (Collection $g, int|string $id) => [
                'curso_id' => (int) $id > 0 ? (int) $id : null,
                'nome' => (int) $id > 0 ? (string) $g->first()['curso'] : self::SEM_CURSO,
                'pessoas' => $g->pluck('pessoa')->unique()->count(),
                'municipios' => $g->pluck('municipio_id')->filter()->unique()->count(),
                'turmas' => $g->pluck('turma_id')->unique()->count(),
            ])
            ->sort(fn ($a, $b) => [$b['pessoas'], $a['nome']] <=> [$a['pessoas'], $b['nome']])
            ->values()
            ->all();
    }

    /**
     * Pessoas distintas por municipio DA PESSOA; sem municipio, uma linha propria.
     *
     * @return list<array{municipio_id: ?int, nome: string, redec: ?string, pessoas: int, cursos: int}>
     */
    public function pessoasPorMunicipio(): array
    {
        $catalogo = $this->catalogo();

        return $this->pessoas()
            ->groupBy(fn ($l) => $l['municipio_id'] ?? 0)
            ->map(function (Collection $g, int|string $id) use ($catalogo) {
                $id = (int) $id;
                $m = $id > 0 ? ($catalogo[$id] ?? null) : null;

                return [
                    'municipio_id' => $id > 0 ? $id : null,
                    'nome' => $id === 0 ? 'Município não informado' : ($m['nome'] ?? "Município {$id}"),
                    'redec' => $this->service->nomeDaRedec($m['redec_id'] ?? null),
                    'pessoas' => $g->pluck('pessoa')->unique()->count(),
                    'cursos' => $g->pluck('curso_id')->filter()->unique()->count(),
                ];
            })
            ->sort(fn ($a, $b) => [$b['pessoas'], $a['nome']] <=> [$a['pessoas'], $b['nome']])
            ->values()
            ->all();
    }

    /**
     * Satisfacao dos concluintes (so CONCLUIDO, ignora o filtro de situacoes).
     * Sem recorte de curso/turma, a quebra e por curso; com recorte, por turma
     * (e versao, no EAD), para comparar edicoes do mesmo curso.
     *
     * @return array<string, mixed>
     */
    public function satisfacao(): array
    {
        if ($this->satisfacao !== null) {
            return $this->satisfacao;
        }

        $avaliacoes = $this->avaliacoes();
        $resumo = $this->resumoDeNotas($avaliacoes);
        $porNota = $avaliacoes->groupBy('nota')->map(fn (Collection $g) => (int) $g->sum('total'));
        $detalhaTurma = $this->filtro->filtraCursoOuTurma();

        return $this->satisfacao = $resumo + [
            'distribuicao' => array_map(fn (NotaAvaliacaoCurso $nota) => [
                'nota' => $nota->value,
                'rotulo' => $nota->getLabel(),
                'total' => (int) ($porNota[$nota->value] ?? 0),
                'pct' => $this->percentual((int) ($porNota[$nota->value] ?? 0), $resumo['n']),
            ], NotaAvaliacaoCurso::cases()),
            'por_curso' => $detalhaTurma ? [] : collect($this->satisfacaoPorCurso())
                ->map(fn (array $s) => ['curso_id' => $s['curso_id'], 'nome' => $s['nome'], 'n' => $s['n'], 'media' => $s['media']])
                ->sort(fn ($a, $b) => [$b['n'], $a['nome']] <=> [$a['n'], $b['nome']])
                ->values()
                ->all(),
            'por_turma' => ! $detalhaTurma ? [] : $avaliacoes
                ->groupBy('treinamento_id')
                ->map(fn (Collection $g, int $id) => [
                    'treinamento_id' => $id,
                    'titulo' => $g->first()['titulo'],
                    'versao' => $g->first()['versao'],
                ] + $this->resumoDeNotas($g))
                ->sort(fn ($a, $b) => [$b['n'], $a['titulo']] <=> [$a['n'], $b['titulo']])
                ->values()
                ->all(),
        ];
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
            'porMes' => $this->porMes(),
            'porSexo' => $this->porSexo(),
            'porFaixaEtaria' => $this->porFaixaEtaria(),
            'satisfacao' => $this->satisfacao(),
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
     * Total de participacoes e a particao por situacao de um conjunto de fatos.
     * Fonte unica das colunas participantes/concluidos/em_andamento/desistentes.
     *
     * @param  Collection<int, array<string, mixed>>  $fatos
     * @return array{participantes: int, concluidos: int, em_andamento: int, desistentes: int}
     */
    private function contagens(Collection $fatos): array
    {
        $porSituacao = $fatos->groupBy('situacao')->map(fn (Collection $g) => (int) $g->sum('total'));

        $contagens = ['participantes' => (int) $fatos->sum('total')];
        foreach (SituacaoParticipacao::cases() as $situacao) {
            $contagens[$situacao->chave()] = (int) ($porSituacao[$situacao->value] ?? 0);
        }

        return $contagens;
    }

    /**
     * Quantidade de avaliacoes e media das notas (1 casa; null sem avaliacao).
     * Com $comNotas, acrescenta a contagem por nota (1..5), para a barrinha de
     * distribuicao do card de curso.
     *
     * @param  Collection<int, array{nota: int, total: int}>  $avaliacoes
     * @return array<string, mixed>
     */
    private function resumoDeNotas(Collection $avaliacoes, bool $comNotas = false): array
    {
        $n = (int) $avaliacoes->sum('total');
        $soma = $avaliacoes->sum(fn ($a) => $a['nota'] * $a['total']);
        $resumo = ['n' => $n, 'media' => $n > 0 ? round($soma / $n, 1) : null];

        if (! $comNotas) {
            return $resumo;
        }

        $porNota = $avaliacoes->groupBy('nota')->map(fn (Collection $g) => (int) $g->sum('total'));

        return $resumo + ['notas' => array_map(fn (NotaAvaliacaoCurso $nota) => [
            'nota' => $nota->value,
            'rotulo' => $nota->getLabel(),
            'total' => (int) ($porNota[$nota->value] ?? 0),
        ], NotaAvaliacaoCurso::cases())];
    }

    /**
     * Satisfacao por curso, na MESMA chave de porCurso() (turma sem curso e um
     * curso proprio), para o card e o ranking do bloco de satisfacao baterem.
     *
     * @return array<string, array<string, mixed>>
     */
    private function satisfacaoPorCurso(): array
    {
        return $this->avaliacoes()
            ->groupBy(fn ($a) => self::chaveDoCurso($a))
            ->map(fn (Collection $g) => [
                'curso_id' => $g->first()['curso_id'],
                'nome' => self::nomeDoCurso($g->first()),
            ] + $this->resumoDeNotas($g, true))
            ->all();
    }

    /** @return Collection<int, array<string, mixed>> */
    private function avaliacoes(): Collection
    {
        return $this->avaliacoes ??= $this->service->avaliacoes($this->filtro);
    }

    /** Agrupa por curso do catalogo; turma sem curso e um "curso" proprio. */
    private static function chaveDoCurso(array $linha): string
    {
        return $linha['curso_id'] !== null ? "c:{$linha['curso_id']}" : "t:{$linha['treinamento_id']}";
    }

    /** Nome exibido para a chave de chaveDoCurso(): o curso, ou o titulo da turma avulsa. */
    private static function nomeDoCurso(array $linha): string
    {
        return $linha['curso_id'] !== null ? (string) $linha['curso'] : (string) $linha['titulo'];
    }

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
     * antiga. O municipio e o DA TURMA (EAD nao tem). Fonte unica da tela
     * paginada e do relatorio.
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
                $municipio = $turma['municipio_turma_id'] !== null ? ($catalogo[$turma['municipio_turma_id']] ?? null) : null;

                return [
                    'municipio' => $municipio['nome'] ?? null,
                    'redec' => $this->service->nomeDaRedec($municipio['redec_id'] ?? null),
                    'mesorregiao' => $municipio['mesorregiao'] ?? null,
                    'treinamento' => $turma['titulo'],
                    'ano' => $turma['ano'],
                    'periodo_inicio' => $turma['data_inicio'],
                    'periodo_fim' => $turma['data_fim'],
                ] + $this->contagens($g) + [
                    'masculino' => (int) $g->where('sexo', self::SEXOS['masculino'])->sum('total'),
                    'feminino' => (int) $g->where('sexo', self::SEXOS['feminino'])->sum('total'),
                    'treinamento_id' => $turma['treinamento_id'],
                ];
            })
            ->sort(fn ($a, $b) => [$b['ano'], $b['periodo_inicio'] ?? '', $a['treinamento'], $a['treinamento_id']]
                <=> [$a['ano'], $a['periodo_inicio'] ?? '', $b['treinamento'], $b['treinamento_id']])
            ->map(fn ($l) => array_diff_key($l, ['treinamento_id' => true]))
            ->values();
    }

    /** @return Collection<int, array<string, mixed>> */
    private function pessoas(): Collection
    {
        return $this->pessoas ??= $this->service->pessoas($this->filtro);
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
     * Agregado por municipio de referencia, do maior para o menor. Sem
     * municipio fica de fora: aparece a parte em semMunicipio().
     *
     * @return Collection<int, array{municipio_id: int, participantes: int, concluidos: int, em_andamento: int, desistentes: int, capacitacoes: int}>
     */
    private function porMunicipio(): Collection
    {
        return $this->municipios ??= $this->fatos()
            ->whereNotNull('municipio_id')
            ->groupBy('municipio_id')
            ->map(fn (Collection $g, int $id) => ['municipio_id' => $id] + $this->contagens($g) + [
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
