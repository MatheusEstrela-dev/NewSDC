<?php

declare(strict_types=1);

namespace App\Modules\Compdec\Application\DTOs;

readonly class OrgaoStatisticsDTO
{
    public function __construct(
        public int $totalOrgaos,
        public int $totalCompdecs,
        public int $totalRedecs,
        public int $totalCedecs,
        public int $totalAtivos,
        public int $totalOrfaos,
        public float $percentualCoberturaMunicipal,
        public array $distribuicaoPorEstado, // ['MG' => 50, 'SP' => 30]
    ) {
    }

    public function toArray(): array
    {
        return [
            'total' => $this->totalOrgaos,
            'ativos' => $this->totalAtivos,
            'orfaos' => $this->totalOrfaos,
            'por_tipo' => [
                'compdec' => $this->totalCompdecs,
                'redec' => $this->totalRedecs,
                'cedec' => $this->totalCedecs,
            ],
            'percentual_cobertura_municipal' => $this->percentualCoberturaMunicipal,
            'distribuicao_por_estado' => $this->distribuicaoPorEstado,
        ];
    }
}
