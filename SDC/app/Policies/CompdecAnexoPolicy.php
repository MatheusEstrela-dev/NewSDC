<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Modules\Compdec\Models\CompdecAnexo;
use Illuminate\Auth\Access\HandlesAuthorization;

class CompdecAnexoPolicy
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
        return $user->can('compdec.anexos.view');
    }

    public function view(User $user, CompdecAnexo $anexo): bool
    {
        return $user->can('compdec.anexos.view');
    }

    public function create(User $user): bool
    {
        return $user->can('compdec.anexos.create');
    }

    public function update(User $user, CompdecAnexo $anexo): bool
    {
        return $user->can('compdec.anexos.edit');
    }

    public function delete(User $user, CompdecAnexo $anexo): bool
    {
        return $user->can('compdec.anexos.delete');
    }

    public function download(User $user, CompdecAnexo $anexo): bool
    {
        return $user->can('compdec.anexos.download');
    }
}
