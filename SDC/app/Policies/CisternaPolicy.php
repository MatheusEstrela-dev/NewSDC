<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Modules\Cisterna\Models\Cisterna;
use Illuminate\Auth\Access\HandlesAuthorization;

class CisternaPolicy
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
        return $user->can('cisternas.view');
    }

    public function view(User $user, Cisterna $cisterna): bool
    {
        return $user->can('cisternas.view');
    }

    public function create(User $user): bool
    {
        return $user->can('cisternas.create');
    }

    public function update(User $user, Cisterna $cisterna): bool
    {
        return $user->can('cisternas.edit');
    }

    public function delete(User $user, Cisterna $cisterna): bool
    {
        return $user->can('cisternas.delete');
    }

    public function export(User $user): bool
    {
        return $user->can('cisternas.export');
    }
}
