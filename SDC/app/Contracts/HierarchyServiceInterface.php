<?php

namespace App\Contracts;

use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Collection;

interface HierarchyServiceInterface
{
    /**
     * Verifica se o ator pode gerenciar o usuario alvo.
     */
    public function canUserManageTarget(User $actor, User $target): bool;

    /**
     * Verifica se o ator pode atribuir a role especificada.
     */
    public function canUserAssignRole(User $actor, Role $role): bool;

    /**
     * Retorna as roles que o usuario pode atribuir.
     */
    public function getAssignableRolesForUser(User $user): Collection;

    /**
     * Retorna o nivel de hierarquia do usuario.
     */
    public function getUserHierarchyLevel(User $user): int;

    /**
     * Retorna o nivel de hierarquia pelo slug do cargo.
     */
    public function getLevelBySlug(string $slug): int;

    /**
     * Verifica se um cargo e protegido.
     */
    public function isProtectedRole(string $slug): bool;

    /**
     * Verifica se uma permissao e imutavel.
     */
    public function isImmutablePermission(string $permission): bool;

    /**
     * Retorna todos os modulos de permissoes configurados.
     */
    public function getPermissionModules(): array;
}
