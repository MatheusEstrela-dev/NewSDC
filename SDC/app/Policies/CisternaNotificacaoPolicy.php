<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

class CisternaNotificacaoPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('cisternas.notificacoes.view');
    }

    public function view(User $user): bool
    {
        return $user->can('cisternas.notificacoes.view');
    }

    public function create(User $user): bool
    {
        return $user->can('cisternas.notificacoes.create');
    }

    public function update(User $user): bool
    {
        return $user->can('cisternas.notificacoes.edit');
    }

    public function delete(User $user): bool
    {
        return $user->can('cisternas.notificacoes.delete');
    }
}
