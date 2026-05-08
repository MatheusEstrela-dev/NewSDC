<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Modules\Compdec\Models\CompdecPlanoContingencia;
use Illuminate\Auth\Access\HandlesAuthorization;

class CompdecPlanoContingenciaPolicy
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
        return $user->can('compdec.plano.view');
    }

    public function view(User $user, CompdecPlanoContingencia $plano): bool
    {
        return $user->can('compdec.plano.view');
    }

    public function create(User $user): bool
    {
        return $user->can('compdec.plano.create');
    }

    public function update(User $user, CompdecPlanoContingencia $plano): bool
    {
        return $user->can('compdec.plano.edit');
    }

    public function delete(User $user, CompdecPlanoContingencia $plano): bool
    {
        return $user->can('compdec.plano.delete');
    }

    /**
     * Permission separada de edit — geralmente CEDEC/admin.
     */
    public function aprovar(User $user, CompdecPlanoContingencia $plano): bool
    {
        return $user->can('compdec.plano.aprovar');
    }

    public function download(User $user, CompdecPlanoContingencia $plano): bool
    {
        return $user->can('compdec.plano.download');
    }
}
