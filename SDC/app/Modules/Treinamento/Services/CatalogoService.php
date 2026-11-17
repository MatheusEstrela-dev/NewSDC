<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Services;

use App\Modules\Treinamento\Models\Curso;
use App\Modules\Treinamento\Models\Treinamento;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Catalogo de cursos: o que varias turmas tem em comum. Curso com turma nunca
 * e apagado, so inativado (some das opcoes de novas turmas, as antigas mantem o vinculo).
 */
class CatalogoService
{
    /**
     * @param  array{busca?: ?string, ativo?: ?string}  $filtros
     */
    public function listar(array $filtros = [], int $porPagina = 15): LengthAwarePaginator
    {
        $query = Curso::query()->withCount('turmas');

        $busca = trim((string) ($filtros['busca'] ?? ''));
        if ($busca !== '') {
            $termo = '%'.mb_strtolower($busca).'%';
            $query->where(function ($q) use ($termo) {
                $q->whereRaw('lower(nome) like ?', [$termo])
                    ->orWhereRaw("lower(coalesce(sigla, '')) like ?", [$termo]);
            });
        }

        $ativo = $filtros['ativo'] ?? null;
        if ($ativo === '1' || $ativo === '0') {
            $query->where('ativo', $ativo === '1');
        }

        return $query->orderBy('nome')->paginate($porPagina)->withQueryString();
    }

    /**
     * Cursos ativos para o select da turma. `$incluirId` mantem o curso atual
     * da turma na lista mesmo que ele tenha sido inativado depois.
     *
     * @return list<array{id: int, nome: string, sigla: ?string}>
     */
    public function opcoesAtivas(?int $incluirId = null): array
    {
        return Curso::query()
            ->where(function ($q) use ($incluirId) {
                $q->where('ativo', true);
                if ($incluirId !== null) {
                    $q->orWhere('id', $incluirId);
                }
            })
            ->orderBy('nome')
            ->get(['id', 'nome', 'sigla'])
            ->map(fn (Curso $c) => ['id' => $c->id, 'nome' => $c->nome, 'sigla' => $c->sigla])
            ->all();
    }

    public function criar(array $dados, ?int $userId = null): Curso
    {
        $this->garantirNomeUnico($dados['nome']);

        return Curso::create([
            'nome' => trim($dados['nome']),
            'sigla' => $this->vazioParaNulo($dados['sigla'] ?? null),
            'descricao' => $this->vazioParaNulo($dados['descricao'] ?? null),
            'ativo' => (bool) ($dados['ativo'] ?? true),
            'created_by' => $userId,
        ]);
    }

    public function atualizar(Curso $curso, array $dados): Curso
    {
        if (array_key_exists('nome', $dados)) {
            $this->garantirNomeUnico($dados['nome'], $curso->id);
            $dados['nome'] = trim($dados['nome']);
        }
        if (array_key_exists('ativo', $dados) && $dados['ativo'] === null) {
            unset($dados['ativo']);
        }
        foreach (['sigla', 'descricao'] as $campo) {
            if (array_key_exists($campo, $dados)) {
                $dados[$campo] = $this->vazioParaNulo($dados[$campo]);
            }
        }

        $curso->update(array_intersect_key($dados, array_flip(['nome', 'sigla', 'descricao', 'ativo'])));

        return $curso->refresh();
    }

    /** Minusculas, sem acento, espacos colapsados e sem pontuacao nas pontas. */
    public static function normalizarNome(string $nome): string
    {
        $limpo = mb_strtolower(Str::ascii($nome));
        $limpo = preg_replace('/\s+/u', ' ', $limpo) ?? $limpo;

        return trim($limpo, " \t\n\r\0\x0B.,;:!?-_/\\|()[]{}\"'");
    }

    /**
     * Liga as turmas sem curso a um curso do catalogo pelo titulo normalizado,
     * criando o curso do grupo quando ainda nao existir um com o mesmo nome.
     *
     * @return array{grupos: list<array{curso: string, curso_existente: bool, turmas: int}>, cursos_criados: int, turmas_ligadas: int}
     */
    public function vincularPorTitulo(bool $simular): array
    {
        $turmas = Treinamento::query()->whereNull('curso_id')->get(['id', 'titulo']);
        $porChave = $turmas->groupBy(fn (Treinamento $t) => self::normalizarNome((string) $t->titulo))
            ->reject(fn ($grupo, $chave) => $chave === '');

        $existentes = Curso::query()->get(['id', 'nome'])
            ->mapWithKeys(fn (Curso $c) => [self::normalizarNome($c->nome) => $c->id]);

        $relatorio = ['grupos' => [], 'cursos_criados' => 0, 'turmas_ligadas' => 0];

        $aplicar = function () use ($porChave, $existentes, $simular, &$relatorio) {
            foreach ($porChave as $chave => $grupo) {
                $nome = $this->tituloMaisFrequente($grupo);
                $cursoId = $existentes->get($chave);
                $jaExistia = $cursoId !== null;

                if (! $simular) {
                    if (! $jaExistia) {
                        $cursoId = Curso::create(['nome' => $nome, 'ativo' => true])->id;
                    }
                    Treinamento::query()->whereIn('id', $grupo->pluck('id'))->update(['curso_id' => $cursoId]);
                }

                $relatorio['grupos'][] = ['curso' => $nome, 'curso_existente' => $jaExistia, 'turmas' => $grupo->count()];
                $relatorio['cursos_criados'] += $jaExistia ? 0 : 1;
                $relatorio['turmas_ligadas'] += $grupo->count();
            }
        };

        $simular ? $aplicar() : DB::connection(config('database.treinamento_connection'))->transaction($aplicar);

        return $relatorio;
    }

    /** Titulo mais frequente do grupo (empate: o primeiro), com espacos aparados. */
    private function tituloMaisFrequente(Collection $grupo): string
    {
        $titulos = $grupo->map(fn (Treinamento $t) => trim((string) $t->titulo));

        return (string) $titulos->countBy()->sortDesc()->keys()->first();
    }

    private function garantirNomeUnico(string $nome, ?int $ignorarId = null): void
    {
        $chave = self::normalizarNome($nome);

        $duplicado = Curso::query()
            ->when($ignorarId, fn ($q) => $q->where('id', '!=', $ignorarId))
            ->get(['id', 'nome'])
            ->first(fn (Curso $c) => self::normalizarNome($c->nome) === $chave);

        if ($duplicado) {
            throw new \DomainException("Ja existe um curso chamado \"{$duplicado->nome}\" no catalogo.");
        }
    }

    private function vazioParaNulo(?string $valor): ?string
    {
        $valor = $valor === null ? null : trim($valor);

        return $valor === '' ? null : $valor;
    }
}
