<?php

namespace App\Policies;

use App\Contracts\HierarchyServiceInterface;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

/**
 * Policy base abstrata que centraliza logica comum de autorizacao.
 *
 * Integra com config/permissions.php para:
 * - Super-admin bypass centralizado
 * - Verificacoes de hierarquia
 * - Protecao de roles/permissions imutaveis
 *
 * Todas as policies devem estender desta classe.
 */
abstract class BasePolicy
{
    use HandlesAuthorization;

    protected HierarchyServiceInterface $hierarchyService;

    public function __construct(HierarchyServiceInterface $hierarchyService)
    {
        $this->hierarchyService = $hierarchyService;
    }

    /**
     * Hook executado antes de qualquer verificacao de policy.
     * Super-admin (level 0) tem acesso total - centralizado aqui.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        return null;
    }

    /**
     * Verifica se usuario e super-admin (level 0).
     */
    protected function isSuperAdmin(User $user): bool
    {
        return $user->getHierarchyLevel() === $this->getSuperAdminLevel();
    }

    /**
     * Retorna o nivel do super-admin do config.
     */
    protected function getSuperAdminLevel(): int
    {
        return config('permissions.levels.super-admin', 0);
    }

    /**
     * Verifica se ator pode gerenciar o alvo baseado em hierarquia.
     * Ator com nivel MENOR (maior autoridade) pode gerenciar.
     */
    protected function canManageTarget(User $actor, User $target): bool
    {
        return $this->hierarchyService->canUserManageTarget($actor, $target);
    }

    /**
     * Verifica se ator pode modificar uma role baseado em hierarquia.
     * So pode modificar roles com nivel MAIOR que o seu.
     */
    protected function canModifyRoleByLevel(User $actor, int $roleLevel): bool
    {
        $actorLevel = $actor->getHierarchyLevel();

        if ($actorLevel === $this->getSuperAdminLevel()) {
            return true;
        }

        return $actorLevel < $roleLevel;
    }

    /**
     * Verifica se uma role e protegida (config).
     */
    protected function isProtectedRole(string $slug): bool
    {
        return $this->hierarchyService->isProtectedRole($slug);
    }

    /**
     * Verifica se uma permissao e imutavel (config).
     */
    protected function isImmutablePermission(string $permission): bool
    {
        return $this->hierarchyService->isImmutablePermission($permission);
    }

    /**
     * Retorna Response de negacao por hierarquia insuficiente.
     */
    protected function denyHierarchy(string $action = 'realizar esta acao'): Response
    {
        return Response::deny("Voce nao pode {$action} em usuarios/cargos com hierarquia igual ou superior a sua.");
    }

    /**
     * Retorna Response de negacao por falta de permissao.
     */
    protected function denyPermission(string $permission): Response
    {
        return Response::deny("Voce nao tem a permissao '{$permission}' necessaria para esta acao.");
    }

    /**
     * Retorna Response de negacao por protecao.
     */
    protected function denyProtected(string $entity = 'item'): Response
    {
        return Response::deny("Este {$entity} e protegido e nao pode ser modificado.");
    }

    /**
     * Verifica permissao e retorna Response apropriado.
     */
    protected function checkPermissionOrDeny(User $user, string $permission): Response
    {
        return $user->can($permission)
            ? Response::allow()
            : $this->denyPermission($permission);
    }

    /**
     * Retorna nivel de hierarquia pelo slug da role.
     */
    protected function getLevelBySlug(string $slug): int
    {
        return $this->hierarchyService->getLevelBySlug($slug);
    }

    /**
     * Verifica se o usuario pode atribuir uma role especifica.
     */
    protected function canAssignRole(User $user, $role): bool
    {
        return $this->hierarchyService->canUserAssignRole($user, $role);
    }
}
