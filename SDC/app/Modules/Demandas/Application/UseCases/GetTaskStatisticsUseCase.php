<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Application\UseCases;

use App\Models\User;
use App\Modules\Demandas\Application\DTOs\TaskStatisticsDTO;
use App\Modules\Demandas\Domain\Repositories\TaskRepositoryInterface;

/**
 * Use Case: Obter Estatísticas de Tasks
 *
 * Para dashboard do usuário ou agente TI
 */
class GetTaskStatisticsUseCase
{
    public function __construct(
        private readonly TaskRepositoryInterface $repository
    ) {
    }

    /**
     * Executar obtenção de estatísticas
     *
     * @param array $filters
     * @param User $user
     */
    public function execute(array $filters, User $user): TaskStatisticsDTO
    {
        // Se não for agente TI, filtrar apenas suas solicitações
        if (! $user->can('demandas.manage')) {
            $filters['solicitante_id'] = $user->id;
        }

        $statistics = $this->repository->getStatistics($filters);

        return TaskStatisticsDTO::fromArray($statistics);
    }
}
