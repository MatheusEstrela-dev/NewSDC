<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Services;

use App\Modules\Decretacoes\Services\RedecService;
use App\Modules\Treinamento\DTOs\IndicadoresFiltroDTO;
use App\Modules\Treinamento\Enums\TipoTreinamento;
use App\Modules\Treinamento\Support\ConexaoTreinamento;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Numeros do painel de indicadores das Capacitacoes.
 *
 * Capacitado = inscricao APROVADA com concluido_em preenchido (presencial e
 * EAD; quem grava e o ConclusaoService). Turma cancelada ou excluida e
 * inscricao excluida nao contam.
 *
 * O Treinamento vive em outro banco: a agregacao (GROUP BY) acontece la, por
 * municipio_id, e o municipio/REDEC/populacao/coordenadas entram numa segunda
 * consulta no banco do SDC (catalogo cacheado). Nunca ha JOIN entre os bancos.
 *
 * Todo bloco parte de baseCapacitados(), entao um filtro vale igual em todos.
 */
class IndicadoresService
{
    private const CACHE_TTL = 600;

    private const CACHE_CHAVE_CATALOGO = 'treinamento:indicadores:municipios_mg:v1';

    /** Ano de referencia da capacitacao: quando a turma aconteceu, com fallbacks. */
    private const ANO_DA_TURMA = 'EXTRACT(YEAR FROM COALESCE(t.data_inicio, t.publicado_em::date, t.created_at::date))::int';

    /** Consulta de uma requisicao: o controller cria uma por request e os blocos a compartilham. */
    public function para(IndicadoresFiltroDTO $filtro): IndicadoresConsulta
    {
        return new IndicadoresConsulta($filtro, $this);
    }

    /**
     * Unica agregacao do painel: um total por turma x mes de conclusao x sexo,
     * ja com os dados da turma. Todo bloco (exceto a serie por ano) deriva disto.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function fatos(IndicadoresFiltroDTO $filtro): Collection
    {
        return $this->baseCapacitados($filtro)
            ->select('t.id', 't.titulo', 't.municipio_id', 't.data_inicio', 't.data_fim', 'i.sexo')
            ->selectRaw(self::ANO_DA_TURMA . ' AS ano')
            ->selectRaw("TO_CHAR(i.concluido_em, 'YYYY-MM') AS mes")
            ->selectRaw('COUNT(*) AS total')
            ->groupBy('t.id', 't.titulo', 't.municipio_id', 't.data_inicio', 't.data_fim', 'i.sexo', 'ano', 'mes')
            ->get()
            ->map(fn ($l) => [
                'treinamento_id' => (int) $l->id,
                'titulo' => (string) $l->titulo,
                'municipio_id' => $l->municipio_id !== null ? (int) $l->municipio_id : null,
                'data_inicio' => $l->data_inicio !== null ? substr((string) $l->data_inicio, 0, 10) : null,
                'data_fim' => $l->data_fim !== null ? substr((string) $l->data_fim, 0, 10) : null,
                'sexo' => $l->sexo,
                'ano' => (int) $l->ano,
                'mes' => (string) $l->mes,
                'total' => (int) $l->total,
            ]);
    }

    /**
     * Serie historica: ignora o filtro de ano, por isso tem consulta propria.
     * Capacitacoes contam no ano da turma; participantes, no ano da conclusao.
     *
     * @return list<array{ano: int, capacitacoes: int, participantes: int}>
     */
    public function porAno(IndicadoresFiltroDTO $filtro): array
    {
        $linhas = $this->baseCapacitados($filtro->semAno())
            ->select('t.id')
            ->selectRaw(self::ANO_DA_TURMA . ' AS ano_turma')
            ->selectRaw('EXTRACT(YEAR FROM i.concluido_em)::int AS ano_conclusao')
            ->selectRaw('COUNT(*) AS total')
            ->groupBy('t.id', 'ano_turma', 'ano_conclusao')
            ->get();

        $capacitacoes = $linhas->groupBy('ano_turma')->map(fn (Collection $g) => $g->pluck('id')->unique()->count());
        $participantes = $linhas->groupBy('ano_conclusao')->map(fn (Collection $g) => (int) $g->sum('total'));

        return $capacitacoes->keys()->merge($participantes->keys())
            ->map(fn ($a) => (int) $a)->unique()->sort()->values()
            ->map(fn (int $ano) => [
                'ano' => $ano,
                'capacitacoes' => (int) ($capacitacoes[$ano] ?? 0),
                'participantes' => (int) ($participantes[$ano] ?? 0),
            ])->all();
    }

    /**
     * Valores dos filtros. Anos, cursos e municipios vem so de quem tem
     * capacitado: opcao sem resultado seria um filtro que sempre volta vazio.
     *
     * @return array<string, mixed>
     */
    public function opcoes(): array
    {
        $linhas = $this->baseCapacitados(new IndicadoresFiltroDTO)
            ->select('t.id', 't.titulo', 't.municipio_id')
            ->selectRaw('EXTRACT(YEAR FROM i.concluido_em)::int AS ano')
            ->groupBy('t.id', 't.titulo', 't.municipio_id', 'ano')
            ->get();

        $anos = $linhas->pluck('ano')->map(fn ($a) => (int) $a)->unique()->sortDesc()->values()->all();

        $treinamentos = $linhas->unique('id')->sortBy('titulo')
            ->map(fn ($l) => ['id' => (int) $l->id, 'titulo' => (string) $l->titulo])
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
            'treinamentos' => $treinamentos,
            'tipos' => array_map(
                fn (TipoTreinamento $t) => ['value' => $t->value, 'label' => $t->getLabel()],
                TipoTreinamento::cases()
            ),
        ];
    }

    // ------------------------------------------------------------ internos

    /**
     * Inscricoes capacitadas com os filtros aplicados, ja unidas a turma
     * (alias i e t). Cada bloco acrescenta select/group by em cima disto.
     */
    private function baseCapacitados(IndicadoresFiltroDTO $filtro): Builder
    {
        $consulta = ConexaoTreinamento::conexao()
            ->table('treinamento.inscricoes as i')
            ->join('treinamento.treinamentos as t', 't.id', '=', 'i.treinamento_id')
            ->where('i.status', 'APROVADA')
            ->whereNotNull('i.concluido_em')
            ->whereNull('i.deleted_at')
            ->whereNull('t.deleted_at')
            ->where('t.status', '<>', 'CANCELADO');

        if ($filtro->ano !== null) {
            // Intervalo, nao EXTRACT: assim o indice de concluido_em vale.
            $consulta->where('i.concluido_em', '>=', "{$filtro->ano}-01-01")
                ->where('i.concluido_em', '<', ($filtro->ano + 1) . '-01-01');
        }
        if ($filtro->municipioId !== null) {
            $consulta->where('t.municipio_id', $filtro->municipioId);
        }
        if ($filtro->redecId !== null) {
            // REDEC mora no banco do SDC: traduz para os municipios dela e filtra por id.
            $consulta->whereIn('t.municipio_id', $this->municipiosDaRedec($filtro->redecId));
        }
        if ($filtro->treinamentoId !== null) {
            $consulta->where('t.id', $filtro->treinamentoId);
        }
        if ($filtro->tipo !== null) {
            $consulta->where('t.tipo', $filtro->tipo);
        }

        return $consulta;
    }

    /**
     * Catalogo de municipios de MG com REDEC, mesorregiao, populacao e
     * coordenadas, indexado por municipios.id. Cacheado: muda raramente e todo
     * bloco do painel precisa dele.
     *
     * @return array<int, array{nome: string, mesorregiao: ?string, lat: ?float, lng: ?float, redec_id: ?int, populacao: float}>
     */
    public function catalogoMunicipios(): array
    {
        return Cache::remember(self::CACHE_CHAVE_CATALOGO, self::CACHE_TTL, function (): array {
            $linhas = DB::table('municipios')
                ->leftJoin('cedec_municipio', 'cedec_municipio.Codmundv', '=', 'municipios.codigo_ibge')
                ->where('municipios.uf', 'MG')
                ->select([
                    'municipios.id', 'municipios.nome', 'municipios.mesorregiao',
                    'municipios.latitude', 'municipios.longitude',
                    'cedec_municipio.redec_id', 'cedec_municipio.populacao',
                ])
                ->get();

            $catalogo = [];
            foreach ($linhas as $l) {
                $catalogo[(int) $l->id] = [
                    'nome' => (string) $l->nome,
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
}
