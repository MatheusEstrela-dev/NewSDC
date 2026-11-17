<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Services;

use App\Models\User;
use App\Modules\Decretacoes\Services\RedecService;
use App\Modules\Treinamento\DTOs\ConsultaEditalFiltroDTO;
use App\Modules\Treinamento\Models\Cidadao;
use App\Modules\Treinamento\Models\Curso;
use App\Modules\Treinamento\Support\ConexaoTreinamento;
use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Consulta para edital: quem (e de quais municipios) concluiu o(s) curso(s) do
 * catalogo. Tem o curso = inscricao APROVADA com concluido_em em turma dele.
 *
 * A qualificacao (GROUP BY pessoa HAVING) roda no banco do treinamento; nome,
 * CPF, orgao e municipio entram em consultas em lote no banco do SDC. Nunca ha
 * JOIN entre os bancos nem consulta por pessoa.
 */
class ConsultaEditalService
{
    public const POR_PAGINA = 20;

    private const LOTE = 2000;

    private const TIPO_SERVIDOR = User::class;

    private const TIPO_CIDADAO = Cidadao::class;

    public function __construct(private readonly IndicadoresService $indicadores) {}

    /** @return array{cursos: list<array{id: int, nome: string}>, redecs: list<array{id: mixed, nome: string}>, municipios: list<array{id: int, nome: string}>} */
    public function opcoes(): array
    {
        $municipios = [];
        foreach ($this->indicadores->catalogoMunicipios() as $id => $m) {
            $municipios[] = ['id' => $id, 'nome' => $m['nome']];
        }
        usort($municipios, fn ($a, $b) => strcmp($a['nome'], $b['nome']));

        return [
            'cursos' => Curso::query()->ativos()->orderBy('nome')->get(['id', 'nome'])
                ->map(fn ($c) => ['id' => (int) $c->id, 'nome' => (string) $c->nome])->all(),
            'redecs' => array_values(array_map(
                fn ($r) => ['id' => $r->id, 'nome' => $r->label()],
                RedecService::all()
            )),
            'municipios' => $municipios,
        ];
    }

    /**
     * Pessoas qualificadas (pagina de 20, ordenada por nome) e municipios.
     *
     * @return array{pessoas: LengthAwarePaginator, municipios: list<array<string, mixed>>, total_pessoas: int, total_municipios: int}
     */
    public function consultar(ConsultaEditalFiltroDTO $filtro): array
    {
        $chaves = $this->qualificadas($filtro);
        $municipios = $this->municipios($chaves);
        $ordenadas = $this->ordenadas($chaves, $nomes);

        $pagina = Paginator::resolveCurrentPage();
        $recorte = $ordenadas->forPage($pagina, self::POR_PAGINA)->values();

        return [
            'pessoas' => new LengthAwarePaginator(
                $this->detalhar($recorte, $filtro, $nomes),
                $ordenadas->count(),
                self::POR_PAGINA,
                $pagina,
                ['path' => Paginator::resolveCurrentPath()],
            ),
            'municipios' => $municipios,
            'total_pessoas' => $ordenadas->count(),
            'total_municipios' => count(array_filter($municipios, fn ($m) => $m['municipio_id'] !== null)),
        ];
    }

    /**
     * Todas as pessoas qualificadas, sem paginar (exportacao).
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function todas(ConsultaEditalFiltroDTO $filtro): Collection
    {
        $ordenadas = $this->ordenadas($this->qualificadas($filtro), $nomes);

        return $this->detalhar($ordenadas, $filtro, $nomes);
    }

    /** CPF mascarado (***.456.789-**), igual ao da credencial do servidor. */
    public static function mascararCpf(?string $cpf): string
    {
        $d = preg_replace('/\D/', '', (string) $cpf);

        return strlen($d) === 11 ? '***.' . substr($d, 3, 3) . '.' . substr($d, 6, 3) . '-**' : '';
    }

    // ------------------------------------------------------------ internos

    /**
     * Pessoas que tem o(s) curso(s), com o municipio da pessoa (o da inscricao
     * concluida mais recente). Uma consulta no banco do treinamento.
     *
     * @return Collection<int, array{type: string, id: int, municipio_id: ?int}>
     */
    private function qualificadas(ConsultaEditalFiltroDTO $filtro): Collection
    {
        if (! $filtro->temCursos()) {
            return collect();
        }

        $minimo = $filtro->modo === ConsultaEditalFiltroDTO::MODO_TODOS ? count($filtro->cursos) : 1;

        $agrupada = $this->concluidas($filtro->cursos)
            ->select('i.inscrito_type', 'i.inscrito_id')
            ->selectRaw('(array_agg(i.municipio_inscrito_id ORDER BY i.concluido_em DESC, i.id DESC))[1] AS municipio_id')
            ->groupBy('i.inscrito_type', 'i.inscrito_id')
            ->havingRaw('COUNT(DISTINCT t.curso_id) >= ?', [$minimo]);

        $consulta = ConexaoTreinamento::conexao()->query()->fromSub($agrupada, 'q')
            ->select('q.inscrito_type', 'q.inscrito_id', 'q.municipio_id');

        if ($filtro->municipioId !== null) {
            $consulta->where('q.municipio_id', $filtro->municipioId);
        }
        if ($filtro->redecId !== null) {
            $consulta->whereIn('q.municipio_id', $this->municipiosDaRedec($filtro->redecId));
        }

        return $consulta->get()->map(fn ($l) => [
            'type' => (string) $l->inscrito_type,
            'id' => (int) $l->inscrito_id,
            'municipio_id' => $l->municipio_id !== null ? (int) $l->municipio_id : null,
        ]);
    }

    /** @param list<int> $cursos */
    private function concluidas(array $cursos): Builder
    {
        return ConexaoTreinamento::conexao()
            ->table('treinamento.inscricoes as i')
            ->join('treinamento.treinamentos as t', 't.id', '=', 'i.treinamento_id')
            ->where('i.status', 'APROVADA')
            ->whereNotNull('i.concluido_em')
            ->whereNull('i.deleted_at')
            ->whereNull('t.deleted_at')
            ->where('t.status', '<>', 'CANCELADO')
            ->whereIn('t.curso_id', $cursos);
    }

    /**
     * Ordena por nome (os nomes vem em lote do SDC e do cadastro de cidadaos).
     *
     * @param  Collection<int, array{type: string, id: int, municipio_id: ?int}>  $chaves
     * @param  array<string, string>|null  $nomes  saida: nome por identidade
     * @return Collection<int, array{type: string, id: int, municipio_id: ?int}>
     */
    private function ordenadas(Collection $chaves, ?array &$nomes): Collection
    {
        $nomes = $this->nomes($chaves);

        return $chaves->sortBy(fn ($c) => mb_strtolower($nomes[$this->chave($c)] ?? ''))->values();
    }

    /**
     * @param  Collection<int, array{type: string, id: int, municipio_id: ?int}>  $chaves
     * @return list<array{municipio_id: ?int, municipio: string, redec: ?string, pessoas: int}>
     */
    private function municipios(Collection $chaves): array
    {
        $catalogo = $this->indicadores->catalogoMunicipios();

        return $chaves->groupBy(fn ($c) => $c['municipio_id'] ?? 0)
            ->map(function (Collection $g, int|string $id) use ($catalogo) {
                $id = (int) $id;
                $m = $id > 0 ? ($catalogo[$id] ?? null) : null;

                return [
                    'municipio_id' => $id > 0 ? $id : null,
                    'municipio' => $id === 0 ? 'Município não informado' : ($m['nome'] ?? "Município {$id}"),
                    'redec' => $this->indicadores->nomeDaRedec($m['redec_id'] ?? null),
                    'pessoas' => $g->count(),
                ];
            })
            ->sort(fn ($a, $b) => [$b['pessoas'], $a['municipio']] <=> [$a['pessoas'], $b['municipio']])
            ->values()->all();
    }

    /**
     * Nome por pessoa (consultas em lote, uma por banco).
     *
     * @param  Collection<int, array{type: string, id: int, municipio_id: ?int}>  $chaves
     * @return array<string, string>
     */
    private function nomes(Collection $chaves): array
    {
        $nomes = [];
        foreach ($this->idsPorTipo($chaves) as $tipo => $ids) {
            foreach (array_chunk($ids, self::LOTE) as $lote) {
                foreach ($this->tabelaDaPessoa($tipo)->whereIn('id', $lote)->get(['id', 'name']) as $l) {
                    $nomes["{$tipo}:{$l->id}"] = (string) $l->name;
                }
            }
        }

        return $nomes;
    }

    /**
     * Linhas finais com CPF, orgao e datas de conclusao, em lote.
     *
     * @param  Collection<int, array{type: string, id: int, municipio_id: ?int}>  $chaves
     * @param  array<string, string>  $nomes
     * @return Collection<int, array<string, mixed>>
     */
    private function detalhar(Collection $chaves, ConsultaEditalFiltroDTO $filtro, array $nomes): Collection
    {
        if ($chaves->isEmpty()) {
            return collect();
        }

        $catalogo = $this->indicadores->catalogoMunicipios();
        $cpfs = [];
        $orgaos = [];
        foreach ($this->idsPorTipo($chaves) as $tipo => $ids) {
            foreach (array_chunk($ids, self::LOTE) as $lote) {
                if ($tipo === self::TIPO_SERVIDOR) {
                    $users = DB::table('users')->whereIn('id', $lote)->get(['id', 'cpf', 'orgao_principal_id']);
                    $nomesOrgao = DB::table('compdec_orgaos')
                        ->whereIn('id', $users->pluck('orgao_principal_id')->filter()->unique()->all())
                        ->pluck('nome', 'id');
                    foreach ($users as $u) {
                        $cpfs["{$tipo}:{$u->id}"] = $u->cpf;
                        $orgaos["{$tipo}:{$u->id}"] = $u->orgao_principal_id !== null ? ($nomesOrgao[$u->orgao_principal_id] ?? null) : null;
                    }
                } else {
                    foreach ($this->tabelaDaPessoa($tipo)->whereIn('id', $lote)->get(['id', 'cpf']) as $c) {
                        $cpfs["{$tipo}:{$c->id}"] = $c->cpf;
                    }
                }
            }
        }

        $cursos = $this->conclusoes($chaves, $filtro->cursos);

        return $chaves->map(function (array $c) use ($nomes, $cpfs, $orgaos, $cursos, $catalogo) {
            $k = $this->chave($c);
            $m = $c['municipio_id'] !== null ? ($catalogo[$c['municipio_id']] ?? null) : null;

            return [
                'identidade' => $k,
                'nome' => $nomes[$k] ?? '',
                'cpf' => self::mascararCpf($cpfs[$k] ?? null),
                'tipo' => $c['type'] === self::TIPO_SERVIDOR ? 'Servidor' : 'Cidadão',
                'orgao' => $orgaos[$k] ?? null,
                'municipio' => $c['municipio_id'] === null ? null : ($m['nome'] ?? "Município {$c['municipio_id']}"),
                'redec' => $this->indicadores->nomeDaRedec($m['redec_id'] ?? null),
                'cursos' => $cursos[$k] ?? [],
            ];
        })->values();
    }

    /**
     * Curso e data de conclusao (a mais recente) por pessoa, so dos cursos da consulta.
     *
     * @param  Collection<int, array{type: string, id: int, municipio_id: ?int}>  $chaves
     * @param  list<int>  $cursos
     * @return array<string, list<array{curso_id: int, nome: string, concluido_em: string}>>
     */
    private function conclusoes(Collection $chaves, array $cursos): array
    {
        $saida = [];
        foreach ($this->idsPorTipo($chaves) as $tipo => $ids) {
            foreach (array_chunk($ids, self::LOTE) as $lote) {
                $linhas = $this->concluidas($cursos)
                    ->join('treinamento.cursos as c', 'c.id', '=', 't.curso_id')
                    ->where('i.inscrito_type', $tipo)
                    ->whereIn('i.inscrito_id', $lote)
                    ->groupBy('i.inscrito_id', 'c.id', 'c.nome')
                    ->orderBy('c.nome')
                    ->get(['i.inscrito_id', 'c.id as curso_id', 'c.nome', DB::raw('MAX(i.concluido_em) AS concluido_em')]);

                foreach ($linhas as $l) {
                    $saida["{$tipo}:{$l->inscrito_id}"][] = [
                        'curso_id' => (int) $l->curso_id,
                        'nome' => (string) $l->nome,
                        'concluido_em' => substr((string) $l->concluido_em, 0, 10),
                    ];
                }
            }
        }

        return $saida;
    }

    /** Servidor mora no banco do SDC; cidadao, no do treinamento. */
    private function tabelaDaPessoa(string $tipo): Builder
    {
        return $tipo === self::TIPO_SERVIDOR
            ? DB::table('users')
            : ConexaoTreinamento::conexao()->table('cadastro.cidadaos');
    }

    /**
     * @param  Collection<int, array{type: string, id: int, municipio_id: ?int}>  $chaves
     * @return array<string, list<int>>
     */
    private function idsPorTipo(Collection $chaves): array
    {
        return $chaves->groupBy('type')->map(fn (Collection $g) => $g->pluck('id')->unique()->values()->all())->all();
    }

    /** @param array{type: string, id: int} $c */
    private function chave(array $c): string
    {
        return "{$c['type']}:{$c['id']}";
    }

    /** @return list<int> */
    private function municipiosDaRedec(int $redecId): array
    {
        $ids = [];
        foreach ($this->indicadores->catalogoMunicipios() as $id => $m) {
            if ($m['redec_id'] === $redecId) {
                $ids[] = $id;
            }
        }

        return $ids;
    }
}
