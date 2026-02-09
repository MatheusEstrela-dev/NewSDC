<?php

namespace App\Traits;

use App\Contracts\HierarchyServiceInterface;
use App\Models\Role;
use Illuminate\Support\Collection;

/**
 * Trait que adiciona capacidades de hierarquia ao User.
 * Delega logica complexa ao HierarchyService (DRY).
 */
trait HasHierarchy
{
    /**
     * Retorna o menor hierarchy_level dentre as roles do usuario.
     * Quanto MENOR o numero, MAIOR a hierarquia.
     */
    public function getHierarchyLevel(): int
    {
        $roles = $this->roles;

        if ($roles->isEmpty()) {
            return config('permissions.default_level', 99);
        }

        return $roles->min('hierarchy_level') ?? config('permissions.default_level', 99);
    }

    /**
     * Retorna o nivel de hierarquia de um cargo pelo slug.
     */
    public static function getLevelBySlug(string $slug): int
    {
        return app(HierarchyServiceInterface::class)->getLevelBySlug($slug);
    }

    /**
     * Verifica se o usuario atual pode gerenciar outro usuario.
     * Delega ao HierarchyService.
     */
    public function canManage($targetUser): bool
    {
        return app(HierarchyServiceInterface::class)->canUserManageTarget($this, $targetUser);
    }

    /**
     * Verifica se pode atribuir uma role especifica.
     * Delega ao HierarchyService.
     */
    public function canAssignRole(Role $role): bool
    {
        return app(HierarchyServiceInterface::class)->canUserAssignRole($this, $role);
    }

    /**
     * Retorna roles que o usuario pode atribuir a outros.
     * Delega ao HierarchyService.
     */
    public function getAssignableRoles(): Collection
    {
        return app(HierarchyServiceInterface::class)->getAssignableRolesForUser($this);
    }

    /**
     * Verifica se possui hierarquia superior a outro usuario.
     */
    public function hasHigherHierarchyThan($user): bool
    {
        return $this->getHierarchyLevel() < $user->getHierarchyLevel();
    }

    /**
     * Verifica se possui a mesma hierarquia ou superior.
     */
    public function hasSameOrHigherHierarchyThan($user): bool
    {
        return $this->getHierarchyLevel() <= $user->getHierarchyLevel();
    }

    /**
     * Verifica se e super admin (level 0).
     */
    public function isSuperAdmin(): bool
    {
        return $this->getHierarchyLevel() === 0;
    }

    /**
     * Verifica se o usuario possui um cargo protegido.
     */
    public function hasProtectedRole(): bool
    {
        $protectedRoles = config('permissions.protected_roles', ['super-admin']);

        foreach ($this->roles as $role) {
            if (in_array($role->slug ?? $role->name, $protectedRoles)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Retorna os modulos de permissoes do config.
     */
    public static function getPermissionModules(): array
    {
        return app(HierarchyServiceInterface::class)->getPermissionModules();
    }

    /**
     * Retorna todas as permissoes definidas no config como array flat.
     */
    public static function getAllConfigPermissions(): array
    {
        $modules = config('permissions.modules', []);
        $permissions = [];

        foreach ($modules as $moduleName => $groups) {
            foreach ($groups as $groupName => $actions) {
                foreach ($actions as $actionKey => $permissionSlug) {
                    $permissions[] = $permissionSlug;
                }
            }
        }

        return $permissions;
    }

    /**
     * Verifica se uma permissao e imutavel.
     */
    public static function isImmutablePermission(string $permission): bool
    {
        return app(HierarchyServiceInterface::class)->isImmutablePermission($permission);
    }

    /**
     * Verifica se um cargo e protegido.
     */
    public static function isProtectedRole(string $slug): bool
    {
        return app(HierarchyServiceInterface::class)->isProtectedRole($slug);
    }
}
