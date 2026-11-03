<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Modules\NotasTecnicas\Models\NotaTecnica;

/**
 * Rascunho e invisivel para o leitor: so quem redige (edit) ou aprova
 * (publicar) enxerga uma nota antes da publicacao -- o aprovador precisa ver o
 * que vai colocar em vigor. Publicada e revogada ficam abertas a todo leitor; a
 * revogada continua consultavel, marcada como obsoleta.
 */
class NotaTecnicaPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('notas_tecnicas.notas.view');
    }

    public function view(User $user, NotaTecnica $nota): bool
    {
        if (! $user->can('notas_tecnicas.notas.view')) {
            return false;
        }

        return $nota->status->visivelParaLeitor() || self::veRascunho($user);
    }

    public function create(User $user): bool
    {
        return $user->can('notas_tecnicas.notas.create');
    }

    public function update(User $user, NotaTecnica $nota): bool
    {
        return $user->can('notas_tecnicas.notas.edit');
    }

    public function publicar(User $user, NotaTecnica $nota): bool
    {
        return $user->can('notas_tecnicas.notas.publicar');
    }

    public function delete(User $user, NotaTecnica $nota): bool
    {
        return $user->can('notas_tecnicas.notas.delete');
    }

    public function verLeituras(User $user, NotaTecnica $nota): bool
    {
        return $user->can('notas_tecnicas.leituras.view');
    }

    /** Fonte unica da regra de rascunho: a policy e a listagem usam a mesma. */
    public static function veRascunho(User $user): bool
    {
        return $user->can('notas_tecnicas.notas.edit') || $user->can('notas_tecnicas.notas.publicar');
    }
}
