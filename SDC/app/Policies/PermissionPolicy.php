<?php

namespace App\Policies;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Policy para gerenciamento de permissoes.
 * Apenas super-admin pode criar/editar/deletar permissoes.
 * Estende BasePolicy para herdar super-admin bypass.
 */
class PermissionPolicy extends BasePolicy
{
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
        return $this->isSuperAdmin($user);
    }

    public function update(User $user, Permission $permission): Response
    {
        if ($permission->is_immutable || $this->isImmutablePermission($permission->name)) {
            return Response::deny('Esta permissao e imutavel e nao pode ser editada.');
        }

        return $this->isSuperAdmin($user)
            ? Response::allow()
            : Response::deny('Apenas Super Admins podem editar permissoes.');
    }

    public function delete(User $user, Permission $permission): Response
    {
        if ($permission->is_immutable || $this->isImmutablePermission($permission->name)) {
            return Response::deny('Esta permissao e imutavel e nao pode ser deletada.');
        }

        if ($permission->roles()->count() > 0) {
            return Response::deny('Nao e possivel deletar uma permissao que esta associada a roles.');
        }

        return $this->isSuperAdmin($user)
            ? Response::allow()
            : Response::deny('Apenas Super Admins podem deletar permissoes.');
    }

    public function restore(User $user, Permission $permission): bool
    {
        return $this->isSuperAdmin($user);
    }

    public function forceDelete(User $user, Permission $permission): bool
    {
        return false;
    }
}
