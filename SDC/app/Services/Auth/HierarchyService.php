<?php

namespace App\Services\Auth;

use App\Contracts\HierarchyServiceInterface;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Collection;

class HierarchyService implements HierarchyServiceInterface
{
    /**
     * Verifica se o ator pode gerenciar o usuario alvo.
     */
    public function canUserManageTarget(User $actor, User $target): bool
    {
        $actorLevel = $this->getUserHierarchyLevel($actor);

        if ($actorLevel === $this->getSuperAdminLevel()) {
            return true;
        }

        if ($actor->id === $target->id) {
            return false;
        }

        return $actorLevel < $this->getUserHierarchyLevel($target);
    }

    /**
     * Verifica se o ator pode atribuir a role especificada.
     */
    public function canUserAssignRole(User $actor, Role $role): bool
    {
        $actorLevel = $this->getUserHierarchyLevel($actor);

        if ($actorLevel === $this->getSuperAdminLevel()) {
            return true;
        }

        $roleLevel = $this->getLevelBySlug($role->slug) ?? $role->hierarchy_level;

        return $actorLevel < $roleLevel;
    }

    /**
     * Retorna as roles que o usuario pode atribuir.
     */
    public function getAssignableRolesForUser(User $user): Collection
    {
        $userLevel = $this->getUserHierarchyLevel($user);

        if ($userLevel === $this->getSuperAdminLevel()) {
            return Role::where('is_active', true)
                ->orderBy('hierarchy_level')
                ->get();
        }

        return Role::where('hierarchy_level', '>', $userLevel)
            ->where('is_active', true)
            ->orderBy('hierarchy_level')
            ->get();
    }

    /**
     * Retorna o nivel de hierarquia do usuario.
     */
    public function getUserHierarchyLevel(User $user): int
    {
        return $user->getHierarchyLevel();
    }

    /**
     * Retorna o nivel de hierarquia pelo slug do cargo.
     */
    public function getLevelBySlug(string $slug): int
    {
        return config("permissions.levels.{$slug}", config('permissions.default_level', 99));
    }

    /**
     * Retorna o nivel do super admin.
     */
    protected function getSuperAdminLevel(): int
    {
        return config('permissions.levels.super-admin', 0);
    }

    /**
     * Verifica se um cargo e protegido (nao pode ser editado/deletado).
     */
    public function isProtectedRole(string $slug): bool
    {
        $protected = config('permissions.protected_roles', ['super-admin']);
        return in_array($slug, $protected);
    }

    /**
     * Verifica se uma permissao e imutavel.
     */
    public function isImmutablePermission(string $permission): bool
    {
        $immutable = config('permissions.immutable_permissions', []);
        return in_array($permission, $immutable);
    }

    /**
     * Retorna todos os modulos de permissoes configurados.
     */
    public function getPermissionModules(): array
    {
        return config('permissions.modules', []);
    }
}
