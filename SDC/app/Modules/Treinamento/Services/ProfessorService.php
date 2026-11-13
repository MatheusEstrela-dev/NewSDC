<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Services;

use App\Models\User;
use App\Modules\Treinamento\Enums\TipoTreinamento;
use App\Modules\Treinamento\Models\Inscricao;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Models\TreinamentoProfessor;
use App\Modules\Treinamento\Support\AvisoDeCursos;
use Illuminate\Support\Collection;

class ProfessorService
{
    public const PERMISSAO = 'treinamento.professor.acessar';

    /** Quem edita cursos (treinamento.cursos.edit) gerencia qualquer curso EAD, sem precisar ser designado. */
    public const PERMISSAO_ADMIN = 'treinamento.cursos.edit';

    public function designar(Treinamento $treinamento, int $userId): TreinamentoProfessor
    {
        if (!$treinamento->isEad()) {
            throw new \DomainException('So cursos EAD tem professor designado.');
        }

        $user = User::query()->find($userId);

        if ($user === null || !$user->can(self::PERMISSAO)) {
            throw new \DomainException('O usuario nao possui a permissao de professor.');
        }

        $inscritoComoAluno = Inscricao::query()
            ->porTreinamento($treinamento->id)
            ->where('inscrito_type', User::class)
            ->where('inscrito_id', $userId)
            ->exists();

        if ($inscritoComoAluno) {
            throw new \DomainException('Usuario inscrito como aluno neste curso nao pode ser professor.');
        }

        $vinculo = TreinamentoProfessor::query()->firstOrCreate([
            'treinamento_id' => $treinamento->id,
            'user_id' => $userId,
        ]);

        // O curso passa a aparecer na Area do professor de quem foi designado.
        if ($vinculo->wasRecentlyCreated) {
            AvisoDeCursos::avisar();
        }

        return $vinculo;
    }

    public function remover(Treinamento $treinamento, int $userId): void
    {
        $removidos = TreinamentoProfessor::query()
            ->where('treinamento_id', $treinamento->id)
            ->where('user_id', $userId)
            ->delete();

        if ($removidos > 0) {
            AvisoDeCursos::avisar();
        }
    }

    public function leciona(int $userId, Treinamento $treinamento): bool
    {
        return TreinamentoProfessor::query()
            ->where('treinamento_id', $treinamento->id)
            ->where('user_id', $userId)
            ->exists();
    }

    /** Admin (edita cursos) ou professor designado no curso. */
    public function gerencia(User $usuario, Treinamento $treinamento): bool
    {
        return $usuario->can(self::PERMISSAO_ADMIN)
            || $this->leciona((int) $usuario->getKey(), $treinamento);
    }

    /**
     * Cursos EAD que o usuario gerencia: todos para o admin, so os designados para os demais.
     *
     * @return Collection<int, Treinamento>
     */
    public function cursosDe(User $usuario): Collection
    {
        $query = Treinamento::query()->where('tipo', TipoTreinamento::EAD->value);

        if (!$usuario->can(self::PERMISSAO_ADMIN)) {
            $query->whereIn('id', $this->idsDesignados($usuario));
        }

        return $query->orderByDesc('created_at')->orderByDesc('id')->get();
    }

    /**
     * Ids dos cursos em que o usuario esta designado como professor.
     *
     * @return list<int>
     */
    public function idsDesignados(User $usuario): array
    {
        return TreinamentoProfessor::query()
            ->where('user_id', $usuario->getKey())
            ->pluck('treinamento_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Professores do curso como [{id, name}]. Usuarios ficam em outro banco:
     * segunda consulta, sem join.
     *
     * @return list<array{id: int, name: string}>
     */
    public function listar(Treinamento $treinamento): array
    {
        $ids = $treinamento->professores()->pluck('user_id')->all();

        return User::query()
            ->whereIn('id', $ids)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (User $u) => ['id' => (int) $u->id, 'name' => (string) $u->name])
            ->all();
    }

    /**
     * @return Collection<int, User>
     */
    public function candidatos(?string $busca): Collection
    {
        $query = User::permission(self::PERMISSAO);

        $busca = trim((string) $busca);
        if ($busca !== '') {
            $like = '%'.addcslashes($busca, '\\%_').'%';
            $query->where(fn ($q) => $q->where('name', 'ilike', $like)->orWhere('email', 'ilike', $like));
        }

        return $query->orderBy('name')->limit(20)->get(['id', 'name', 'email']);
    }
}
