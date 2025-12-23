<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class IntegrationPolicy
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
        return $user->can('integrations.view');
    }

    public function view(User $user, $integration): bool
    {
        return $user->can('integrations.view');
    }

    public function create(User $user): bool
    {
        return $user->can('integrations.create');
    }

    public function update(User $user, $integration): bool
    {
        return $user->can('integrations.edit');
    }

    public function delete(User $user, $integration): bool
    {
        return $user->hasAnyRole(['super-admin', 'admin']);
    }

    public function execute(User $user, $integration): bool
    {
        return $user->can('integrations.execute');
    }
}
