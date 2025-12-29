<?php

declare(strict_types=1);

namespace App\Modules\Compdec\Application\UseCases;

use App\Models\User;
use App\Modules\Compdec\Domain\Repositories\OrgaoRepositoryInterface;

class VincularUsuarioUseCase
{
    public function __construct(
        private readonly OrgaoRepositoryInterface $repository
    ) {
    }

    /**
     * Vincular usuário a um órgão
     */
    public function execute(int $orgaoId, int $userId, string $funcao = 'agente', bool $isPrincipal = false): void
    {
        $orgao = $this->repository->find($orgaoId);

        if (!$orgao) {
            throw new \DomainException('Órgão não encontrado');
        }

        $user = User::find($userId);

        if (!$user) {
            throw new \DomainException('Usuário não encontrado');
        }

        // Se for principal, remover principal anterior
        if ($isPrincipal && $user->orgao_principal_id && $user->orgao_principal_id !== $orgaoId) {
            // Atualizar pivot do órgão anterior
            $user->orgaos()->updateExistingPivot($user->orgao_principal_id, ['is_principal' => false]);
        }

        // Adicionar usuário ao órgão
        $orgao->adicionarUsuario($user, $funcao, $isPrincipal);
    }

    /**
     * Desvincular usuário de um órgão
     */
    public function desvincular(int $orgaoId, int $userId): void
    {
        $orgao = $this->repository->find($orgaoId);

        if (!$orgao) {
            throw new \DomainException('Órgão não encontrado');
        }

        $user = User::find($userId);

        if (!$user) {
            throw new \DomainException('Usuário não encontrado');
        }

        $orgao->removerUsuario($user);
    }
}
