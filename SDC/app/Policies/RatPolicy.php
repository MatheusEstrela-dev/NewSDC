<?php

declare(strict_types=1);

namespace App\Policies;

use App\Modules\Rat\Models\Rat;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Policy para o módulo RAT (Relatório de Atividades).
 * Controla acesso granular por recurso na tabela rat_ocorrencias.
 */
class RatPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('rat.protocolos.view');
    }

    public function view(User $user, Rat $ocorrencia): bool
    {
        return $user->can('rat.protocolos.view');
    }

    public function create(User $user): bool
    {
        return $user->can('rat.protocolos.create');
    }

    /** Edição: criador pode editar próprio rascunho; demais precisam da permissão explícita. */
    public function update(User $user, Rat $ocorrencia): Response
    {
        if (!$ocorrencia->isFinalized() && (string) $user->id === $ocorrencia->created_by) {
            return Response::allow();
        }

        return $this->checkPermissionOrDeny($user, 'rat.protocolos.edit');
    }

    public function delete(User $user, Rat $ocorrencia): Response
    {
        return $this->checkPermissionOrDeny($user, 'rat.protocolos.delete');
    }

    public function finalize(User $user, Rat $ocorrencia): Response
    {
        if ($ocorrencia->isFinalized()) {
            return Response::deny('Esta ocorrência já está finalizada.');
        }

        return $this->checkPermissionOrDeny($user, 'rat.protocolos.finalize');
    }

    public function export(User $user): bool
    {
        return $user->can('rat.protocolos.export');
    }
}