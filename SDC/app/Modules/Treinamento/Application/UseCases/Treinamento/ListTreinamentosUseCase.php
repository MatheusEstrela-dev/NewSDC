<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Application\UseCases\Treinamento;

use App\Modules\Treinamento\Domain\Repositories\TreinamentoRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListTreinamentosUseCase
{
    public function __construct(
        private readonly TreinamentoRepositoryInterface $repository
    ) {
    }

    public function execute(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->findAll($filters, $perPage);
    }
}
