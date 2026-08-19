<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

class CisternaLotePolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('cisternas.lotes.view');
    }

    public function view(User $user): bool
    {
        return $user->can('cisternas.lotes.view');
    }

    public function create(User $user): bool
    {
        return $user->can('cisternas.lotes.create');
    }

    public function update(User $user): bool
    {
        return $user->can('cisternas.lotes.edit');
    }

    public function delete(User $user): bool
    {
        return $user->can('cisternas.lotes.delete');
    }
}
