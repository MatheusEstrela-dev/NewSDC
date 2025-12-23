<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProtocoloPolicy
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
        return $user->can('rat.protocolos.view');
    }

    public function view(User $user, $protocolo): bool
    {
        return $user->can('rat.protocolos.view');
    }

    public function create(User $user): bool
    {
        return $user->can('rat.protocolos.create');
    }

    public function update(User $user, $protocolo): bool
    {
        return $user->can('rat.protocolos.edit');
    }

    public function delete(User $user, $protocolo): bool
    {
        return $user->can('rat.protocolos.delete');
    }

    public function restore(User $user, $protocolo): bool
    {
        return $user->hasAnyRole(['super-admin', 'admin']);
    }

    public function forceDelete(User $user, $protocolo): bool
    {
        return $user->hasRole('super-admin');
    }

    public function finalize(User $user, $protocolo): bool
    {
        return $user->can('rat.protocolos.finalize');
    }
}
