<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    use HandlesAuthorization;

    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('users.view');
    }

    public function view(User $user, User $model): bool
    {
        if ($user->id === $model->id) {
            return true;
        }

        return $user->can('users.view');
    }

    public function create(User $user): bool
    {
        return $user->can('users.create');
    }

    public function update(User $user, User $model): bool
    {
        if ($user->id === $model->id) {
            return true;
        }

        return $user->can('users.edit');
    }

    public function delete(User $user, User $model): Response
    {
        if ($user->id === $model->id) {
            return Response::deny('Voce nao pode deletar sua propria conta.');
        }

        if ($model->hasRole('super-admin')) {
            return Response::deny('Super Admins nao podem ser deletados.');
        }

        return $user->can('users.delete')
            ? Response::allow()
            : Response::deny('Voce nao tem permissao para deletar usuarios.');
    }

    public function restore(User $user, User $model): bool
    {
        return $user->hasAnyRole(['super-admin', 'admin']);
    }

    public function forceDelete(User $user, User $model): bool
    {
        return $user->hasRole('super-admin');
    }

    public function assignRole(User $user, User $model): bool
    {
        return $user->can('roles.edit');
    }

    public function removeRole(User $user, User $model): Response
    {
        if ($model->hasRole('super-admin')) {
            return Response::deny('Nao e possivel remover role de Super Admin.');
        }

        return $user->can('roles.edit')
            ? Response::allow()
            : Response::deny('Voce nao tem permissao para gerenciar roles.');
    }
}
