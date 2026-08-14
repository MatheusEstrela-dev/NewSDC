<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

class CisternaOrdemServicoPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('cisternas.ordens-servico.view');
    }

    public function view(User $user): bool
    {
        return $user->can('cisternas.ordens-servico.view');
    }

    public function create(User $user): bool
    {
        return $user->can('cisternas.ordens-servico.create');
    }

    public function update(User $user): bool
    {
        return $user->can('cisternas.ordens-servico.edit');
    }

    public function delete(User $user): bool
    {
        return $user->can('cisternas.ordens-servico.delete');
    }

    /**
     * Timeline do lote: uniao da trilha da OS com as movimentacoes de
     * beneficiarios cujo ordem_servico_id apontou para ela.
     */
    public function history(User $user): bool
    {
        return $user->can('cisternas.ordens-servico.history');
    }
}
