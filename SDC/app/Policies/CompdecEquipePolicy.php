<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Modules\Compdec\Models\CompdecEquipe;
use Illuminate\Auth\Access\HandlesAuthorization;

class CompdecEquipePolicy
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
        return $user->can('compdec.equipe.view');
    }

    public function view(User $user, CompdecEquipe $equipe): bool
    {
        return $user->can('compdec.equipe.view');
    }

    public function create(User $user): bool
    {
        return $user->can('compdec.equipe.create');
    }

    public function update(User $user, CompdecEquipe $equipe): bool
    {
        return $user->can('compdec.equipe.edit');
    }

    public function delete(User $user, CompdecEquipe $equipe): bool
    {
        return $user->can('compdec.equipe.delete');
    }
}
