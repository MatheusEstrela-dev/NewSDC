<?php

declare(strict_types=1);

namespace App\Modules\Rat\Application\Services;

use App\Modules\Rat\Application\DTOs\DeleteRatDTO;
use App\Modules\Rat\Application\DTOs\FinalizeRatDTO;
use App\Modules\Rat\Domain\Entities\Rat;
use App\Modules\Rat\Domain\Repositories\RatRepositoryInterface;
use DomainException;

/**
 * Service para operacoes de negocio do modulo RAT.
 * Segue os principios SRP (logica de negocio) e DIP (depende de interface).
 */
class RatService
{
    public function __construct(
        protected readonly RatRepositoryInterface $repository
    ) {}

    /**
     * Finaliza um RAT aplicando regras de dominio.
     *
     * @throws DomainException Se as regras de dominio nao forem atendidas.
     */
    public function finalize(FinalizeRatDTO $dto): Rat
    {
        $rat = $this->repository->find($dto->id);

        if ($rat === null) {
            throw new DomainException("RAT com ID {$dto->id} nao encontrado.");
        }

        $this->validateFinalization($rat);

        return $this->repository->update($dto->id, [
            'status' => 'finalizado',
            'finalizado_em' => now(),
            'finalizado_por' => $dto->userId,
            'observacao_finalizacao' => $dto->observacao,
        ]);
    }

    /**
     * Valida se o RAT pode ser finalizado.
     *
     * @throws DomainException Se a finalizacao nao for permitida.
     */
    private function validateFinalization(Rat $rat): void
    {
        $status = $rat->status ?? null;

        if ($status === 'finalizado') {
            throw new DomainException('Este RAT ja foi finalizado.');
        }

        if ($status === 'rascunho') {
            throw new DomainException(
                'RAT em rascunho nao pode ser finalizado. Preencha os dados obrigatorios primeiro.'
            );
        }

        if ($status === 'cancelado') {
            throw new DomainException('RAT cancelado nao pode ser finalizado.');
        }
    }

    /**
     * Verifica se um RAT pode ser finalizado sem lancar excecao.
     */
    public function canFinalize(int|string $id): bool
    {
        $rat = $this->repository->find($id);

        if ($rat === null) {
            return false;
        }

        $status = $rat->status ?? null;

        return !in_array($status, ['finalizado', 'rascunho', 'cancelado'], true);
    }

    /**
     * Exclui um RAT aplicando regras de dominio.
     *
     * @throws DomainException Se a exclusao nao for permitida.
     */
    public function delete(DeleteRatDTO $dto): bool
    {
        $rat = $this->repository->find($dto->id);

        if ($rat === null) {
            throw new DomainException("RAT com ID {$dto->id} nao encontrado.");
        }

        $this->validateDeletion($rat);

        return $this->repository->delete($dto->id);
    }

    /**
     * Valida se o RAT pode ser excluido.
     *
     * @throws DomainException Se a exclusao nao for permitida.
     */
    private function validateDeletion(Rat $rat): void
    {
        $status = $rat->status ?? null;

        if ($status === 'finalizado') {
            throw new DomainException('RAT finalizado nao pode ser excluido.');
        }

        if ($status === 'em_andamento') {
            throw new DomainException('RAT em andamento nao pode ser excluido.');
        }
    }

    /**
     * Verifica se um RAT pode ser excluido sem lancar excecao.
     */
    public function canDelete(int|string $id): bool
    {
        $rat = $this->repository->find($id);

        if ($rat === null) {
            return false;
        }

        $status = $rat->status ?? null;

        return !in_array($status, ['finalizado', 'em_andamento'], true);
    }
}
