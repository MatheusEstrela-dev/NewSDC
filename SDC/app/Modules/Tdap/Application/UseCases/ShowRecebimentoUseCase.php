<?php

namespace App\Modules\Tdap\Application\UseCases;

use App\Modules\Tdap\Domain\Entities\Recebimento;
use App\Modules\Tdap\Domain\Repositories\RecebimentoRepositoryInterface;

class ShowRecebimentoUseCase
{
    public function __construct(
        private readonly RecebimentoRepositoryInterface $recebimentoRepository
    ) {}

    public function execute(int $id): ?Recebimento
    {
        $recebimento = $this->recebimentoRepository->findById($id);

        if (!$recebimento) {
            throw new \DomainException('Recebimento não encontrado');
        }

        return $recebimento;
    }
}
