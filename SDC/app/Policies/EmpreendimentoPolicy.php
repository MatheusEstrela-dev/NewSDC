<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class EmpreendimentoPolicy
{
    use HandlesAuthorization;

    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('pae.empreendimentos.view');
    }

    public function view(User $user, $empreendimento): bool
    {
        return $user->can('pae.empreendimentos.view');
    }

    public function create(User $user): bool
    {
        return $user->can('pae.empreendimentos.create');
    }

    public function update(User $user, $empreendimento): bool
    {
        return $user->can('pae.empreendimentos.edit');
    }

    public function delete(User $user, $empreendimento): bool
    {
        return $user->can('pae.empreendimentos.delete');
    }

    public function restore(User $user, $empreendimento): bool
    {
        return $user->hasAnyRole(['super-admin', 'admin']);
    }

    public function forceDelete(User $user, $empreendimento): bool
    {
        return $user->hasRole('super-admin');
    }

    public function approve(User $user, $empreendimento): bool
    {
        return $user->can('pae.empreendimentos.approve');
    }
}
