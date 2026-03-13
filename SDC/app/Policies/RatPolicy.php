<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Rat\RatOcorrencia;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Policy para o módulo RAT (Relatório de Atendimento a Emergência).
 *
 * Controla acesso granular por recurso, incluindo regra de negócio:
 * o criador pode editar o próprio rascunho mesmo sem permissão explícita.
 */
class RatPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('rat.protocolos.view');
    }

    public function view(User $user, RatOcorrencia $ocorrencia): bool
    {
        return $user->can('rat.protocolos.view');
    }

    public function create(User $user): bool
    {
        return $user->can('rat.protocolos.create');
    }

    /**
     * Edição: criador pode editar o próprio rascunho;
     * demais usuários precisam da permissão explícita.
     */
    public function update(User $user, RatOcorrencia $ocorrencia): Response
    {
        if ($ocorrencia->isRascunho() && $user->id === $ocorrencia->created_by) {
            return Response::allow();
        }

        return $this->checkPermissionOrDeny($user, 'rat.protocolos.edit');
    }

    public function delete(User $user, RatOcorrencia $ocorrencia): Response
    {
        return $this->checkPermissionOrDeny($user, 'rat.protocolos.delete');
    }

    public function finalize(User $user, RatOcorrencia $ocorrencia): Response
    {
        if ($ocorrencia->isFinalizado()) {
            return Response::deny('Este protocolo já está finalizado.');
        }

        return $this->checkPermissionOrDeny($user, 'rat.protocolos.finalize');
    }

    public function export(User $user): bool
    {
        return $user->can('rat.protocolos.export');
    }
}
