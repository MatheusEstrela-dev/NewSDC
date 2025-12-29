<?php

declare(strict_types=1);

namespace App\Modules\Compdec\Application\UseCases;

use App\Modules\Compdec\Application\DTOs\OrgaoStatisticsDTO;
use App\Modules\Compdec\Domain\Repositories\OrgaoRepositoryInterface;

class GetOrgaoStatisticsUseCase
{
    public function __construct(
        private readonly OrgaoRepositoryInterface $repository
    ) {
    }

    /**
     * Obter estatísticas gerais dos órgãos
     */
    public function execute(): OrgaoStatisticsDTO
    {
        $stats = $this->repository->getStatistics();

        return new OrgaoStatisticsDTO(
            totalOrgaos: $stats['total'],
            totalCompdecs: $stats['total_compdecs'],
            totalRedecs: $stats['total_redecs'],
            totalCedecs: $stats['total_cedecs'],
            totalAtivos: $stats['total_ativos'],
            totalOrfaos: $stats['total_orfaos'],
            percentualCoberturaMunicipal: $stats['percentual_cobertura_municipal'],
            distribuicaoPorEstado: $stats['distribuicao_por_estado'],
        );
    }

    /**
     * Obter estatísticas como array
     */
    public function executeAsArray(): array
    {
        return $this->execute()->toArray();
    }
}
