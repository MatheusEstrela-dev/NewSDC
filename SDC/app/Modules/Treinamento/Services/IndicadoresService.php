<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Services;

use App\Modules\Decretacoes\Services\RedecService;
use App\Modules\Treinamento\DTOs\IndicadoresFiltroDTO;
use App\Modules\Treinamento\Enums\SituacaoParticipacao;
use App\Modules\Treinamento\Enums\TipoTreinamento;
use App\Modules\Treinamento\Models\AvaliacaoCurso;
use App\Modules\Treinamento\Support\ConexaoTreinamento;
use App\Modules\Treinamento\Support\RegrasDaParticipacao as Regras;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Numeros do painel de indicadores das Capacitacoes.
 *
 * A base e a PARTICIPACAO (uma pessoa numa turma), com situacao CONCLUIDO,
 * EM_ANDAMENTO ou DESISTENTE, mes de referencia e municipio de referencia
 * calculados ao vivo. As regras moram em Support\RegrasDaParticipacao.
 *
 * O Treinamento vive em outro banco: a agregacao (GROUP BY) acontece la, por
 * municipio_id, e o municipio/REDEC/populacao/coordenadas entram numa segunda
 * consulta no banco do SDC (catalogo cacheado). Nunca ha JOIN entre os bancos.
 *
 * Todo bloco parte de baseParticipacoes(), entao um filtro vale igual em todos.
 */
class IndicadoresService
{
    private const CACHE_TTL = 600;

    private const CACHE_CHAVE_CATALOGO = 'treinamento:indicadores:municipios_mg:v2';

    /** Ano de referencia da capacitacao: quando a turma aconteceu, com fallbacks. */
    private const ANO_DA_TURMA = 'EXTRACT(YEAR FROM COALESCE(t.data_inicio, t.publicado_em::date, t.created_at::date))::int';

    private const MES_DE_REFERENCIA = "TO_CHAR(" . Regras::DATA_REFERENCIA . ", 'YYYY-MM')";

    private const ANO_DE_REFERENCIA = 'EXTRACT(YEAR FROM ' . Regras::DATA_REFERENCIA . ')::int';

    /** Consulta de uma requisicao: o controller cria uma por request e os blocos a compartilham. */
    public function para(IndicadoresFiltroDTO $filtro): IndicadoresConsulta
    {
        return new IndicadoresConsulta($filtro, $this);
    }

    /**
     * Unica agregacao do painel: um total por turma x municipio de referencia x
     * situacao x mes de referencia x sexo x faixa etaria, ja com os dados da
     * turma e do curso. Todo bloco (exceto serie por ano, pessoas e
     * satisfacao) deriva disto.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function fatos(IndicadoresFiltroDTO $filtro): Collection
    {
        $colunas = [
            't.id', 't.titulo', 't.tipo', 't.curso_id', 'c.nome', 't.versao', 't.municipio_id',
            't.data_inicio', 't.data_fim', Regras::PRAZO, Regras::MUNICIPIO, Regras::SITUACAO,
            Regras::FAIXA_ETARIA, 'i.sexo',
        ];

        return $this->baseParticipacoes($filtro)
            ->select([
                't.id', 't.titulo', 't.tipo', 't.curso_id', 'c.nome as curso', 't.versao',
                't.municipio_id as municipio_turma_id', 't.data_inicio', 't.data_fim',
                Regras::PRAZO, Regras::MUNICIPIO, Regras::SITUACAO, Regras::FAIXA_ETARIA, 'i.sexo',
            ])
            ->selectRaw(self::ANO_DA_TURMA . ' AS ano')
            ->selectRaw(self::MES_DE_REFERENCIA . ' AS mes')
            ->selectRaw('COUNT(*) AS total')
            ->groupBy([...$colunas, 'ano', 'mes'])
            ->get()
            ->map(fn ($l) => [
                'treinamento_id' => (int) $l->id,
                'titulo' => (string) $l->titulo,
                'curso_id' => self::inteiroOuNulo($l->curso_id),
                'curso' => $l->curso,
                'versao' => self::versao($l->tipo, $l->versao),
                'municipio_turma_id' => self::inteiroOuNulo($l->municipio_turma_id),
                'municipio_id' => self::inteiroOuNulo($l->municipio_id),
                'data_inicio' => self::data($l->data_inicio),
                'data_fim' => self::data($l->data_fim),
                'prazo' => self::data($l->prazo),
                'situacao' => (string) $l->situacao,
                'faixa' => (string) $l->faixa,
                'sexo' => $l->sexo !== null ? trim((string) $l->sexo) : null,
                'ano' => (int) $l->ano,
                'mes' => (string) $l->mes,
                'total' => (int) $l->total,
            ]);
    }

    /**
     * Uma linha por participacao, com a pessoa, o municipio DA PESSOA (foto da
     * inscricao), o municipio de referencia, a situacao e o curso do catalogo.
     * Base dos blocos de pessoas distintas (por curso, por municipio e no
     * mapa): a contagem de gente distinta precisa da linha, nao de um total.
     *
     * @return Collection<int, array{pessoa: string, municipio_id: ?int, municipio_referencia_id: ?int, situacao: string, curso_id: ?int, curso: ?string, turma_id: int}>
     */
    public function pessoas(IndicadoresFiltroDTO $filtro): Collection
    {
        return $this->baseParticipacoes($filtro)
            ->select([
                'i.inscrito_type', 'i.inscrito_id', 'i.municipio_inscrito_id', Regras::MUNICIPIO,
                Regras::SITUACAO, 't.curso_id', 't.id as turma_id', 'c.nome as curso',
            ])
            ->get()
            ->map(fn ($l) => [
                'pessoa' => $l->inscrito_type . ':' . $l->inscrito_id,
                'municipio_id' => self::inteiroOuNulo($l->municipio_inscrito_id),
                'municipio_referencia_id' => self::inteiroOuNulo($l->municipio_id),
                'situacao' => (string) $l->situacao,
                'curso_id' => self::inteiroOuNulo($l->curso_id),
                'curso' => $l->curso,
                'turma_id' => (int) $l->turma_id,
            ]);
    }

    /**
     * Avaliacoes de satisfacao: so participacoes CONCLUIDAS (no prazo) com os
     * demais filtros; o filtro de situacoes da tela nao vale aqui. Um total por
     * turma x nota x relevancia (RN17; null nas avaliacoes antigas), com o curso
     * e a versao para os recortes. As linhas particionam as avaliacoes: somar
     * `total` por nota continua dando a contagem da nota geral. `comentarios`
     * conta as avaliacoes da linha com sugestao preenchida (nao vazia).
     *
     * @return Collection<int, array{treinamento_id: int, titulo: string, curso_id: ?int, curso: ?string, versao: ?string, nota: int, nota_relevancia: ?int, total: int, comentarios: int}>
     */
    public function avaliacoes(IndicadoresFiltroDTO $filtro): Collection
    {
        return $this->baseParticipacoes($filtro->soConcluidos())
            ->join((new AvaliacaoCurso)->getTable() . ' as av', 'av.inscricao_id', '=', 'i.id')
            ->select(['t.id', 't.titulo', 't.tipo', 't.versao', 't.curso_id', 'c.nome as curso', 'av.nota', 'av.nota_relevancia'])
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw("COUNT(*) FILTER (WHERE NULLIF(BTRIM(av.comentario), '') IS NOT NULL) AS comentarios")
            ->groupBy(['t.id', 't.titulo', 't.tipo', 't.versao', 't.curso_id', 'c.nome', 'av.nota', 'av.nota_relevancia'])
            ->get()
            ->map(fn ($l) => [
                'treinamento_id' => (int) $l->id,
                'titulo' => (string) $l->titulo,
                'curso_id' => self::inteiroOuNulo($l->curso_id),
                'curso' => $l->curso,
                'versao' => self::versao($l->tipo, $l->versao),
                'nota' => (int) $l->nota,
                'nota_relevancia' => self::inteiroOuNulo($l->nota_relevancia),
                'total' => (int) $l->total,
                'comentarios' => (int) $l->comentarios,
            ]);
    }

    /**
     * Ultimas sugestoes/comentarios da pesquisa de satisfacao, no mesmo recorte
     * e base de concluintes de avaliacoes(). Consulta propria com LIMIT: so o
     * texto, a data e a turma/curso; nunca o autor (LGPD).
     *
     * @return list<array{id: int, comentario: string, data: ?string, titulo: string, curso: ?string, versao: ?string}>
     */
    public function sugestoes(IndicadoresFiltroDTO $filtro, int $limite, int $pagina = 1): array
    {
        return $this->baseComentarios($filtro)
            ->select(['av.id', 'av.comentario', 'av.created_at', 't.titulo', 't.tipo', 't.versao', 'c.nome as curso'])
            ->orderByDesc('av.created_at')
            ->orderByDesc('av.id')
            ->offset(max(0, $pagina - 1) * $limite)
            ->limit($limite)
            ->get()
            ->map(fn ($l) => [
                'id' => (int) $l->id,
                'comentario' => trim((string) $l->comentario),
                'data' => self::data($l->created_at),
                'titulo' => (string) $l->titulo,
                'curso' => $l->curso,
                'versao' => self::versao($l->tipo, $l->versao),
            ])
            ->all();
    }

    /** Total de comentarios do recorte (para paginar "Ver todos"). */
    public function totalDeSugestoes(IndicadoresFiltroDTO $filtro): int
    {
        return $this->baseComentarios($filtro)->count();
    }

    private function baseComentarios(IndicadoresFiltroDTO $filtro): Builder
    {
        return $this->baseParticipacoes($filtro->soConcluidos())
            ->join((new AvaliacaoCurso)->getTable() . ' as av', 'av.inscricao_id', '=', 'i.id')
            ->whereRaw("NULLIF(BTRIM(av.comentario), '') IS NOT NULL");
    }

    /**
     * Serie historica: ignora o filtro de ano, por isso tem consulta propria.
     * Capacitacoes contam no ano da turma; participantes, no ano do mes de
     * referencia da participacao.
     *
     * @return list<array{ano: int, capacitacoes: int, participantes: int}>
     */
    public function porAno(IndicadoresFiltroDTO $filtro): array
    {
        $linhas = $this->baseParticipacoes($filtro->semAno())
            ->select('t.id')
            ->selectRaw(self::ANO_DA_TURMA . ' AS ano_turma')
            ->selectRaw(self::ANO_DE_REFERENCIA . ' AS ano_referencia')
            ->selectRaw('COUNT(*) AS total')
            ->groupBy('t.id', 'ano_turma', 'ano_referencia')
            ->get();

        $capacitacoes = $linhas->groupBy('ano_turma')->map(fn (Collection $g) => $g->pluck('id')->unique()->count());
        $participantes = $linhas->groupBy('ano_referencia')->map(fn (Collection $g) => (int) $g->sum('total'));

        return $capacitacoes->keys()->merge($participantes->keys())
            ->map(fn ($a) => (int) $a)->unique()->sort()->values()
            ->map(fn (int $ano) => [
                'ano' => $ano,
                'capacitacoes' => (int) ($capacitacoes[$ano] ?? 0),
                'participantes' => (int) ($participantes[$ano] ?? 0),
            ])->all();
    }

    /**
     * Valores dos filtros. Anos, cursos, turmas e municipios vem so de quem tem
     * participacao: opcao sem resultado seria um filtro que sempre volta vazio.
     *
     * @return array<string, mixed>
     */
    public function opcoes(): array
    {
        $linhas = $this->baseParticipacoes(new IndicadoresFiltroDTO)
            ->select(['t.id', 't.titulo', 't.curso_id', 'c.nome as curso', Regras::MUNICIPIO])
            ->selectRaw(self::ANO_DE_REFERENCIA . ' AS ano')
            ->groupBy(['t.id', 't.titulo', 't.curso_id', 'c.nome', Regras::MUNICIPIO, 'ano'])
            ->get();

        $anos = $linhas->pluck('ano')->filter()->map(fn ($a) => (int) $a)->unique()->sortDesc()->values()->all();

        $cursos = $linhas->whereNotNull('curso_id')->unique('curso_id')
            ->map(fn ($l) => ['id' => (int) $l->curso_id, 'nome' => (string) $l->curso])
            ->sortBy('nome')->values()->all();

        $treinamentos = $linhas->unique('id')->sortBy('titulo')
            ->map(fn ($l) => ['id' => (int) $l->id, 'titulo' => (string) $l->titulo, 'curso_id' => self::inteiroOuNulo($l->curso_id)])
            ->values()->all();

        $catalogo = $this->catalogoMunicipios();
        $municipios = $linhas->pluck('municipio_id')->filter()->map(fn ($id) => (int) $id)->unique()
            ->filter(fn (int $id) => isset($catalogo[$id]))
            ->map(fn (int $id) => ['id' => $id, 'nome' => $catalogo[$id]['nome']])
            ->sortBy('nome')->values()->all();

        return [
            'anos' => $anos,
            'redecs' => array_values(array_map(
                fn ($r) => ['id' => $r->id, 'nome' => $r->label()],
                RedecService::all()
            )),
            'municipios' => $municipios,
            'cursos' => $cursos,
            'treinamentos' => $treinamentos,
            'tipos' => array_map(
                fn (TipoTreinamento $t) => ['value' => $t->value, 'label' => $t->getLabel()],
                TipoTreinamento::cases()
            ),
            'situacoes' => SituacaoParticipacao::toSelectArray(),
        ];
    }

    // ------------------------------------------------------------ internos

    /**
     * Participacoes com os filtros aplicados, unidas a turma (t) e ao curso do
     * catalogo (c). Municipio e REDEC filtram o municipio de referencia; o ano
     * filtra o mes de referencia; situacoes, a situacao calculada. Cada bloco
     * acrescenta select/group by em cima disto.
     */
    private function baseParticipacoes(IndicadoresFiltroDTO $filtro): Builder
    {
        $consulta = Regras::participacoes(ConexaoTreinamento::conexao())
            ->leftJoin('treinamento.cursos as c', 'c.id', '=', 't.curso_id');

        if ($filtro->ano !== null) {
            $consulta->where(Regras::DATA_REFERENCIA, '>=', "{$filtro->ano}-01-01")
                ->where(Regras::DATA_REFERENCIA, '<', ($filtro->ano + 1) . '-01-01');
        }
        if ($filtro->municipioId !== null) {
            $consulta->where(Regras::MUNICIPIO, $filtro->municipioId);
        }
        if ($filtro->redecId !== null) {
            // REDEC mora no banco do SDC: traduz para os municipios dela e filtra por id.
            $consulta->whereIn(Regras::MUNICIPIO, $this->municipiosDaRedec($filtro->redecId));
        }
        if ($filtro->cursoId !== null) {
            $consulta->where('t.curso_id', $filtro->cursoId);
        }
        if ($filtro->treinamentoId !== null) {
            $consulta->where('t.id', $filtro->treinamentoId);
        }
        if ($filtro->tipo !== null) {
            $consulta->where('t.tipo', $filtro->tipo);
        }
        if ($filtro->filtraSituacao()) {
            $consulta->whereIn(Regras::SITUACAO, $filtro->situacoes);
        }

        return $consulta;
    }

    /**
     * Catalogo de municipios de MG com codigo IBGE, REDEC, mesorregiao,
     * populacao e coordenadas, indexado por municipios.id. Cacheado: muda
     * raramente e todo bloco do painel precisa dele.
     *
     * @return array<int, array{nome: string, ibge: string, mesorregiao: ?string, lat: ?float, lng: ?float, redec_id: ?int, populacao: float}>
     */
    public function catalogoMunicipios(): array
    {
        return Cache::remember(self::CACHE_CHAVE_CATALOGO, self::CACHE_TTL, function (): array {
            $linhas = DB::table('municipios')
                ->leftJoin('cedec_municipio', 'cedec_municipio.Codmundv', '=', 'municipios.codigo_ibge')
                ->where('municipios.uf', 'MG')
                ->select([
                    'municipios.id', 'municipios.nome', 'municipios.codigo_ibge', 'municipios.mesorregiao',
                    'municipios.latitude', 'municipios.longitude',
                    'cedec_municipio.redec_id', 'cedec_municipio.populacao',
                ])
                ->get();

            $catalogo = [];
            foreach ($linhas as $l) {
                $catalogo[(int) $l->id] = [
                    'nome' => (string) $l->nome,
                    // O GeoJSON do mapa casa pelo codigo de 7 digitos (codarea).
                    'ibge' => str_pad(trim((string) $l->codigo_ibge), 7, '0', STR_PAD_LEFT),
                    'mesorregiao' => $l->mesorregiao,
                    'lat' => $l->latitude !== null ? (float) $l->latitude : null,
                    'lng' => $l->longitude !== null ? (float) $l->longitude : null,
                    'redec_id' => $l->redec_id !== null ? (int) $l->redec_id : null,
                    'populacao' => (float) ($l->populacao ?? 0),
                ];
            }

            return $catalogo;
        });
    }

    /** @return list<int> */
    private function municipiosDaRedec(int $redecId): array
    {
        $ids = [];
        foreach ($this->catalogoMunicipios() as $id => $municipio) {
            if ($municipio['redec_id'] === $redecId) {
                $ids[] = $id;
            }
        }

        return $ids;
    }

    public function nomeDaRedec(?int $redecId): ?string
    {
        if ($redecId === null) {
            return null;
        }

        $redec = RedecService::all()[$redecId] ?? null;

        return $redec?->label() ?? "REDEC {$redecId}";
    }

    private static function inteiroOuNulo(mixed $valor): ?int
    {
        return $valor !== null ? (int) $valor : null;
    }

    /** Rotulo da versao ("v2.0"), so no EAD: e o unico tipo com trilha versionada. */
    private static function versao(mixed $tipo, mixed $versao): ?string
    {
        return $tipo === TipoTreinamento::EAD->value && $versao !== null ? "v{$versao}.0" : null;
    }

    private static function data(mixed $valor): ?string
    {
        return $valor !== null ? substr((string) $valor, 0, 10) : null;
    }
}
