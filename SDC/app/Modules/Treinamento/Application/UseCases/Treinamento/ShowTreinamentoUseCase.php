<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Application\UseCases\Treinamento;

use App\Modules\Treinamento\Domain\Entities\Treinamento;
use App\Modules\Treinamento\Domain\Repositories\TreinamentoRepositoryInterface;

class ShowTreinamentoUseCase
{
    public function __construct(
        private readonly TreinamentoRepositoryInterface $repository
    ) {
    }

    public function execute(int $id): ?Treinamento
    {
        return $this->repository->find($id);
    }
}
