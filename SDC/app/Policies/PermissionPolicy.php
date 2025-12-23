<?php

namespace App\Policies;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class PermissionPolicy
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
        return $user->can('permissions.view');
    }

    public function view(User $user, Permission $permission): bool
    {
        return $user->can('permissions.view');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('super-admin');
    }

    public function update(User $user, Permission $permission): Response
    {
        if ($permission->is_immutable) {
            return Response::deny('Esta permissao e imutavel e nao pode ser editada.');
        }

        return $user->hasRole('super-admin')
            ? Response::allow()
            : Response::deny('Apenas Super Admins podem editar permissoes.');
    }

    public function delete(User $user, Permission $permission): Response
    {
        if ($permission->is_immutable) {
            return Response::deny('Esta permissao e imutavel e nao pode ser deletada.');
        }

        if ($permission->roles()->count() > 0) {
            return Response::deny('Nao e possivel deletar uma permissao que esta associada a roles.');
        }

        return $user->hasRole('super-admin')
            ? Response::allow()
            : Response::deny('Apenas Super Admins podem deletar permissoes.');
    }

    public function restore(User $user, Permission $permission): bool
    {
        return $user->hasRole('super-admin');
    }

    public function forceDelete(User $user, Permission $permission): bool
    {
        return false;
    }
}
