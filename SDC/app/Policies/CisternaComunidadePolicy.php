<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

class CisternaComunidadePolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('cisternas.comunidades.view');
    }

    public function view(User $user): bool
    {
        return $user->can('cisternas.comunidades.view');
    }

    public function create(User $user): bool
    {
        return $user->can('cisternas.comunidades.create');
    }

    public function update(User $user): bool
    {
        return $user->can('cisternas.comunidades.edit');
    }

    public function delete(User $user): bool
    {
        return $user->can('cisternas.comunidades.delete');
    }
}
