<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class RolePolicy
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
        return $user->can('roles.view');
    }

    public function view(User $user, Role $role): bool
    {
        return $user->can('roles.view');
    }

    public function create(User $user): bool
    {
        return $user->can('roles.create');
    }

    public function update(User $user, Role $role): Response
    {
        if ($role->slug === 'super-admin') {
            return Response::deny('O cargo Super Admin nao pode ser editado.');
        }

        return $user->can('roles.edit')
            ? Response::allow()
            : Response::deny('Voce nao tem permissao para editar roles.');
    }

    public function delete(User $user, Role $role): Response
    {
        if ($role->slug === 'super-admin') {
            return Response::deny('O cargo Super Admin nao pode ser deletado.');
        }

        if ($role->users()->count() > 0) {
            return Response::deny('Nao e possivel deletar um cargo que possui usuarios associados.');
        }

        return $user->can('roles.delete')
            ? Response::allow()
            : Response::deny('Voce nao tem permissao para deletar roles.');
    }

    public function restore(User $user, Role $role): bool
    {
        return $user->hasRole('super-admin');
    }

    public function forceDelete(User $user, Role $role): bool
    {
        return $user->hasRole('super-admin');
    }

    public function attachPermission(User $user, Role $role): bool
    {
        return $user->can('permissions.manage');
    }

    public function detachPermission(User $user, Role $role): Response
    {
        if ($role->slug === 'super-admin') {
            return Response::deny('Permissoes do Super Admin nao podem ser modificadas.');
        }

        return $user->can('permissions.manage')
            ? Response::allow()
            : Response::deny('Voce nao tem permissao para gerenciar permissoes.');
    }
}
