<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Modules\Treinamento\Models\Treinamento;

/**
 * Policy para o módulo de Treinamento (cursos/eventos).
 *
 * As permissões Spatie (treinamento.cursos.*) continuam sendo a fonte de
 * verdade; a Policy só centraliza esse mapeamento no módulo de permissão,
 * em vez de deixar as strings soltas nas rotas.
 */
class TreinamentoPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('treinamento.cursos.view');
    }

    public function view(User $user, Treinamento $treinamento): bool
    {
        return $user->can('treinamento.cursos.view');
    }

    public function create(User $user): bool
    {
        return $user->can('treinamento.cursos.create');
    }

    public function update(User $user, Treinamento $treinamento): bool
    {
        return $user->can('treinamento.cursos.edit');
    }

    public function delete(User $user, Treinamento $treinamento): bool
    {
        return $user->can('treinamento.cursos.delete');
    }

    public function export(User $user): bool
    {
        return $user->can('treinamento.cursos.export');
    }
}
