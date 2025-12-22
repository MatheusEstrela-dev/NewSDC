<?php

namespace App\Modules\Rat\Application\UseCases;

use App\Modules\Rat\Application\DTOs\RatListDTO;
use App\Modules\Rat\Domain\Repositories\RatRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListRatsUseCase
{
    public function __construct(
        private readonly RatRepositoryInterface $repository
    ) {
    }

    public function execute(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->findAll($filters, $perPage);
    }
}

