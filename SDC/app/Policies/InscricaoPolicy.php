<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Modules\Treinamento\Models\Inscricao;

/**
 * Policy para inscrições em Treinamentos (lado da area interna/staff).
 *
 * Ações do próprio inscrito (autoconfirmar presença, ver a própria
 * inscrição) ficam no Portal do Cidadão e não passam por aqui — essa
 * Policy cobre apenas a gestão feita pela equipe (guard "web").
 */
class InscricaoPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('treinamento.inscricoes.view');
    }

    public function view(User $user, Inscricao $inscricao): bool
    {
        return $user->can('treinamento.inscricoes.view');
    }

    public function aprovar(User $user, Inscricao $inscricao): bool
    {
        return $user->can('treinamento.inscricoes.aprovar');
    }

    public function reprovar(User $user, Inscricao $inscricao): bool
    {
        return $user->can('treinamento.inscricoes.reprovar');
    }
}
