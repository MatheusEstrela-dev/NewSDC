<?php

namespace App\Modules\PlanCon\Application\UseCases;

use App\Modules\PlanCon\Application\DTOs\PlanConStatsDTO;
use App\Modules\PlanCon\Domain\Repositories\PlanoContingenciaRepositoryInterface;

class GetPlanConStatsUseCase
{
    public function __construct(
        private readonly PlanoContingenciaRepositoryInterface $repository
    ) {
    }

    public function execute(): PlanConStatsDTO
    {
        $stats = $this->repository->getStatistics();

        return new PlanConStatsDTO(
            totalMunicipios: $stats['totalMunicipios'],
            municipiosComPlano: $stats['municipiosComPlano'],
            municipiosSemPlano: $stats['municipiosSemPlano'],
            percentualComPlano: $stats['percentualComPlano'],
            totalPlanos: $stats['totalPlanos'],
            planosRegulares: $stats['planosRegulares'],
            planosIrregulares: $stats['planosIrregulares'],
            percentualRegulares: $stats['percentualRegulares'],
        );
    }
}
