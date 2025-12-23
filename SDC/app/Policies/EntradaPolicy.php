<?php

namespace App\Policies;

use App\Models\Entrada;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class EntradaPolicy
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
        return $user->can('bi.dashboards.view');
    }

    public function view(User $user, Entrada $entrada): bool
    {
        return $user->can('bi.dashboards.view');
    }
}


