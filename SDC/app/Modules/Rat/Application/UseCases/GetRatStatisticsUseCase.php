<?php

namespace App\Modules\Rat\Application\UseCases;

use App\Modules\Rat\Application\DTOs\RatStatisticsDTO;
use App\Modules\Rat\Domain\Repositories\RatRepositoryInterface;

class GetRatStatisticsUseCase
{
    public function __construct(
        private readonly RatRepositoryInterface $repository
    ) {
    }

    public function execute(array $filters = []): RatStatisticsDTO
    {
        $statistics = $this->repository->getStatistics($filters);

        return new RatStatisticsDTO(
            total: $statistics['total'] ?? 0,
            hoje: $statistics['hoje'] ?? 0,
            esteMes: $statistics['este_mes'] ?? 0,
            esteAno: $statistics['este_ano'] ?? 0,
        );
    }
}

