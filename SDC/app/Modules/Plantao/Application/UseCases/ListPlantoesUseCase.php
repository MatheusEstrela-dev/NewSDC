<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Application\UseCases;

use App\Modules\Plantao\Domain\Repositories\PlantaoRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Use Case: Listar Plantões
 */
class ListPlantoesUseCase
{
    public function __construct(
        private readonly PlantaoRepositoryInterface $repository
    ) {
    }

    public function execute(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->findAll($filters, $perPage);
    }
}
