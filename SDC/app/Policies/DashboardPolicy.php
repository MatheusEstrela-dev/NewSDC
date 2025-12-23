<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DashboardPolicy
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

    public function view(User $user, $dashboard): bool
    {
        return $user->can('bi.dashboards.view');
    }

    public function create(User $user): bool
    {
        return $user->can('bi.dashboards.create');
    }

    public function update(User $user, $dashboard): bool
    {
        return $user->can('bi.dashboards.create');
    }

    public function delete(User $user, $dashboard): bool
    {
        return $user->hasAnyRole(['super-admin', 'admin']);
    }

    public function export(User $user): bool
    {
        return $user->can('bi.reports.export');
    }
}
