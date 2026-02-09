<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Policy para gerenciamento de cargos (roles).
 * Estende BasePolicy para herdar super-admin bypass e helpers de hierarquia.
 */
class RolePolicy extends BasePolicy
{
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
        if ($this->isProtectedRole($role->slug ?? $role->name)) {
            return $this->denyProtected('cargo');
        }

        if (!$this->canModifyRoleByLevel($user, $role->hierarchy_level)) {
            return $this->denyHierarchy('editar');
        }

        return $this->checkPermissionOrDeny($user, 'roles.edit');
    }

    public function delete(User $user, Role $role): Response
    {
        if ($this->isProtectedRole($role->slug ?? $role->name)) {
            return $this->denyProtected('cargo');
        }

        if ($role->users()->count() > 0) {
            return Response::deny('Nao e possivel deletar um cargo que possui usuarios associados.');
        }

        if (!$this->canModifyRoleByLevel($user, $role->hierarchy_level)) {
            return $this->denyHierarchy('deletar');
        }

        return $this->checkPermissionOrDeny($user, 'roles.delete');
    }

    public function restore(User $user, Role $role): bool
    {
        return $this->isSuperAdmin($user);
    }

    public function forceDelete(User $user, Role $role): bool
    {
        return $this->isSuperAdmin($user);
    }

    public function attachPermission(User $user, Role $role): Response
    {
        if (!$user->can('permissions.manage')) {
            return $this->denyPermission('permissions.manage');
        }

        if (!$this->canModifyRoleByLevel($user, $role->hierarchy_level)) {
            return $this->denyHierarchy('modificar permissoes de');
        }

        return Response::allow();
    }

    public function detachPermission(User $user, Role $role): Response
    {
        if ($this->isProtectedRole($role->slug ?? $role->name)) {
            return $this->denyProtected('cargo');
        }

        if (!$this->canModifyRoleByLevel($user, $role->hierarchy_level)) {
            return $this->denyHierarchy('modificar permissoes de');
        }

        return $this->checkPermissionOrDeny($user, 'permissions.manage');
    }
}
