<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Policy para gerenciamento de usuarios.
 * Estende BasePolicy para herdar super-admin bypass e helpers de hierarquia.
 */
class UserPolicy extends BasePolicy
{
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

    public function update(User $user, User $model): Response
    {
        if ($user->id === $model->id) {
            return Response::allow();
        }

        if (!$this->canManageTarget($user, $model)) {
            return $this->denyHierarchy('editar');
        }

        return $this->checkPermissionOrDeny($user, 'users.edit');
    }

    public function delete(User $user, User $model): Response
    {
        if ($user->id === $model->id) {
            return Response::deny('Voce nao pode deletar sua propria conta.');
        }

        if ($this->isSuperAdmin($model)) {
            return $this->denyProtected('Super Admin');
        }

        if (!$this->canManageTarget($user, $model)) {
            return $this->denyHierarchy('deletar');
        }

        return $this->checkPermissionOrDeny($user, 'users.delete');
    }

    public function restore(User $user, User $model): Response
    {
        if (!$user->hasAnyRole(['super-admin', 'admin'])) {
            return Response::deny('Apenas administradores podem restaurar usuarios.');
        }

        if (!$this->canManageTarget($user, $model)) {
            return $this->denyHierarchy('restaurar');
        }

        return Response::allow();
    }

    public function forceDelete(User $user, User $model): bool
    {
        return $this->isSuperAdmin($user);
    }

    public function assignRole(User $user, User $model): Response
    {
        if (!$user->can('roles.edit')) {
            return $this->denyPermission('roles.edit');
        }

        if (!$this->canManageTarget($user, $model)) {
            return $this->denyHierarchy('atribuir roles a');
        }

        return Response::allow();
    }

    public function removeRole(User $user, User $model): Response
    {
        if ($this->isSuperAdmin($model)) {
            return $this->denyProtected('Super Admin');
        }

        if (!$this->canManageTarget($user, $model)) {
            return $this->denyHierarchy('remover roles de');
        }

        return $this->checkPermissionOrDeny($user, 'roles.edit');
    }
}
