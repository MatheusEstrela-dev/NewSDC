<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Modules\Compdec\Models\Orgao;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class OrgaoPolicy
{
    use HandlesAuthorization;

    /**
     * Super-admin sempre tem acesso total (Gate::before global tambem cobre, esse fica como defesa em profundidade).
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('compdec.orgaos.view');
    }

    public function view(User $user, Orgao $orgao): bool
    {
        if (method_exists($user, 'pertenceAoOrgao') && $user->pertenceAoOrgao($orgao->id)) {
            return true;
        }

        return $user->can('compdec.orgaos.view');
    }

    public function create(User $user): bool
    {
        return $user->can('compdec.orgaos.create');
    }

    public function update(User $user, Orgao $orgao): bool
    {
        // Coordenador/agente do proprio orgao podem editar com permissao base
        if (
            isset($user->orgao_principal_id)
            && (int) $user->orgao_principal_id === (int) $orgao->id
            && $user->can('compdec.orgaos.edit')
        ) {
            return true;
        }

        return $user->can('compdec.orgaos.edit');
    }

    public function delete(User $user, Orgao $orgao): Response
    {
        if (! $user->can('compdec.orgaos.delete')) {
            return Response::deny('Voce nao tem permissao para excluir orgaos.');
        }

        if ($orgao->orgaosSubordinados()->exists()) {
            return Response::deny('Nao e possivel excluir um orgao com subordinados. Remova os vinculos primeiro.');
        }

        if ($orgao->usuarios()->exists()) {
            return Response::deny('Nao e possivel excluir um orgao com usuarios vinculados.');
        }

        return Response::allow();
    }

    public function restore(User $user, Orgao $orgao): bool
    {
        return $user->hasAnyRole(['super-admin', 'admin']) && $user->can('compdec.orgaos.edit');
    }

    public function forceDelete(User $user, Orgao $orgao): bool
    {
        return $user->hasRole('super-admin');
    }

    public function export(User $user): bool
    {
        return $user->can('compdec.orgaos.export');
    }
}
