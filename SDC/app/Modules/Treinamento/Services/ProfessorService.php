<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Services;

use App\Models\User;
use App\Modules\Treinamento\Enums\TipoTreinamento;
use App\Modules\Treinamento\Models\Inscricao;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Models\TreinamentoProfessor;
use Illuminate\Support\Collection;

class ProfessorService
{
    public const PERMISSAO = 'treinamento.professor.acessar';

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

        return TreinamentoProfessor::query()->firstOrCreate([
            'treinamento_id' => $treinamento->id,
            'user_id' => $userId,
        ]);
    }

    public function remover(Treinamento $treinamento, int $userId): void
    {
        TreinamentoProfessor::query()
            ->where('treinamento_id', $treinamento->id)
            ->where('user_id', $userId)
            ->delete();
    }

    public function leciona(int $userId, Treinamento $treinamento): bool
    {
        return TreinamentoProfessor::query()
            ->where('treinamento_id', $treinamento->id)
            ->where('user_id', $userId)
            ->exists();
    }

    /**
     * @return Collection<int, Treinamento>
     */
    public function cursosDe(int $userId): Collection
    {
        return Treinamento::query()
            ->where('tipo', TipoTreinamento::EAD->value)
            ->whereIn('id', TreinamentoProfessor::query()->where('user_id', $userId)->select('treinamento_id'))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();
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
