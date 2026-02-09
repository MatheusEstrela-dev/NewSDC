<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Contracts\HierarchyServiceInterface;
use App\Models\User;
use App\Models\Role;
use Symfony\Component\HttpFoundation\Response;

class CheckHierarchy
{
    public function __construct(
        protected HierarchyServiceInterface $hierarchyService
    ) {}

    /**
     * Handle an incoming request.
     *
     * Uso: middleware('hierarchy:user') - verifica se pode gerenciar o user do route binding
     * Uso: middleware('hierarchy:role') - verifica se pode atribuir/editar a role do route binding
     */
    public function handle(Request $request, Closure $next, string $type = 'user'): Response
    {
        $actor = $request->user();

        if (!$actor) {
            abort(401, 'Nao autenticado');
        }

        if ($actor->hasRole('super-admin')) {
            return $next($request);
        }

        switch ($type) {
            case 'user':
                $this->checkUserHierarchy($request, $actor);
                break;
            case 'role':
                $this->checkRoleHierarchy($request, $actor);
                break;
        }

        return $next($request);
    }

    protected function checkUserHierarchy(Request $request, User $actor): void
    {
        $targetUser = $request->route('user');

        if (!$targetUser instanceof User) {
            return;
        }

        if (!$this->hierarchyService->canUserManageTarget($actor, $targetUser)) {
            abort(403, 'Voce nao possui hierarquia suficiente para gerenciar este usuario');
        }
    }

    protected function checkRoleHierarchy(Request $request, User $actor): void
    {
        $roleIds = $request->input('roles', []);

        if (empty($roleIds)) {
            return;
        }

        $roles = Role::whereIn('id', $roleIds)->get();

        foreach ($roles as $role) {
            if (!$this->hierarchyService->canUserAssignRole($actor, $role)) {
                abort(403, "Voce nao pode atribuir o cargo '{$role->name}' (nivel de hierarquia insuficiente)");
            }
        }
    }
}
