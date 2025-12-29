<?php

namespace App\Policies;

use App\Models\User;
use App\Modules\Compdec\Domain\Entities\Orgao;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class OrgaoPolicy
{
    use HandlesAuthorization;

    /**
     * Super-admin sempre tem acesso total
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return null;
    }

    /**
     * Pode visualizar a lista de órgãos?
     */
    public function viewAny(User $user): bool
    {
        return $user->can('compdec.view');
    }

    /**
     * Pode visualizar um órgão específico?
     */
    public function view(User $user, Orgao $orgao): bool
    {
        // Se pertence ao órgão, pode ver
        if ($user->pertenceAoOrgao($orgao->id)) {
            return true;
        }

        return $user->can('compdec.view-details');
    }

    /**
     * Pode criar novos órgãos?
     */
    public function create(User $user): bool
    {
        return $user->can('compdec.create');
    }

    /**
     * Pode editar um órgão?
     */
    public function update(User $user, Orgao $orgao): bool
    {
        // Coordenador pode editar seu próprio órgão
        if ($user->orgao_principal_id === $orgao->id && $user->can('compdec.edit')) {
            return true;
        }

        return $user->can('compdec.manage');
    }

    /**
     * Pode deletar um órgão?
     */
    public function delete(User $user, Orgao $orgao): Response
    {
        // Não pode deletar se tiver subordinados
        if ($orgao->subordinados()->count() > 0) {
            return Response::deny('Não é possível deletar um órgão que possui subordinados. Remova os vínculos hierárquicos primeiro.');
        }

        // Não pode deletar se tiver usuários vinculados
        if ($orgao->usuarios()->count() > 0) {
            return Response::deny('Não é possível deletar um órgão que possui usuários vinculados. Remova os vínculos primeiro.');
        }

        // Não pode deletar órgãos ativos
        if (!$orgao->status->podeSerExcluido()) {
            return Response::deny('Apenas órgãos inativos ou em implantação podem ser deletados.');
        }

        return $user->can('compdec.delete')
            ? Response::allow()
            : Response::deny('Você não tem permissão para deletar órgãos.');
    }

    /**
     * Pode restaurar um órgão deletado?
     */
    public function restore(User $user, Orgao $orgao): bool
    {
        return $user->hasAnyRole(['super-admin', 'admin']) && $user->can('compdec.manage');
    }

    /**
     * Pode deletar permanentemente?
     */
    public function forceDelete(User $user, Orgao $orgao): bool
    {
        return $user->hasRole('super-admin');
    }

    /**
     * Pode vincular/desvincular usuários ao órgão?
     */
    public function vincularUsuarios(User $user, Orgao $orgao): bool
    {
        // Coordenador pode vincular usuários ao seu próprio órgão
        if ($user->orgao_principal_id === $orgao->id && $user->hasRole('coordinator')) {
            return true;
        }

        return $user->can('compdec.vincular-usuarios');
    }

    /**
     * Pode gerenciar a hierarquia (definir órgão superior)?
     */
    public function gerenciarHierarquia(User $user, Orgao $orgao): bool
    {
        return $user->can('compdec.gerenciar-hierarquia');
    }

    /**
     * Pode visualizar estatísticas?
     */
    public function viewStatistics(User $user): bool
    {
        return $user->can('compdec.view-statistics');
    }

    /**
     * Pode exportar dados?
     */
    public function export(User $user): bool
    {
        return $user->can('compdec.export');
    }

    /**
     * Pode importar órgãos em lote?
     */
    public function import(User $user): bool
    {
        return $user->can('compdec.import');
    }
}
