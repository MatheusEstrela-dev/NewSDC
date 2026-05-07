<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Modules\Compdec\Models\Prefeitura;
use Illuminate\Auth\Access\HandlesAuthorization;

class PrefeituraPolicy
{
    use HandlesAuthorization;

    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return null;
    }

    public function view(User $user, ?Prefeitura $prefeitura = null): bool
    {
        return $user->can('compdec.prefeitura.view');
    }

    public function update(User $user, ?Prefeitura $prefeitura = null): bool
    {
        return $user->can('compdec.prefeitura.edit');
    }
}
