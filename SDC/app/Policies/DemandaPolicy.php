<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Modules\Demandas\Models\Demanda;

class DemandaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('demandas.chamados.view');
    }

    public function view(User $user, Demanda $demanda): bool
    {
        return $this->viewAny($user) && (
            $user->can('demandas.chamados.manage')
            || (int) $demanda->solicitante_id === (int) $user->id
            || (int) $demanda->atribuido_para_id === (int) $user->id
        );
    }

    public function create(User $user): bool
    {
        return $user->can('demandas.chamados.create');
    }

    public function update(User $user, Demanda $demanda): bool
    {
        return $user->can('demandas.chamados.edit') && (
            $user->can('demandas.chamados.manage')
            || (int) $demanda->atribuido_para_id === (int) $user->id
        );
    }

    public function comment(User $user, Demanda $demanda): bool
    {
        return $this->view($user, $demanda);
    }

    public function manage(User $user, Demanda $demanda): bool
    {
        return $user->can('demandas.chamados.manage');
    }

    public function delete(User $user, Demanda $demanda): bool
    {
        return $user->can('demandas.chamados.delete') && $user->can('demandas.chamados.manage');
    }

    public function export(User $user): bool
    {
        return $user->can('demandas.chamados.export');
    }
}
