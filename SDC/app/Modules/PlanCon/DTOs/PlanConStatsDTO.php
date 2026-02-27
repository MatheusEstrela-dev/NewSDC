<?php

declare(strict_types=1);

namespace App\Modules\PlanCon\DTOs;

class PlanConStatsDTO
{
    public function __construct(
        public readonly int $totalMunicipios,
        public readonly int $municipiosComPlano,
        public readonly int $municipiosSemPlano,
        public readonly float $percentualComPlano,
        public readonly int $totalPlanos,
        public readonly int $planosRegulares,
        public readonly int $planosIrregulares,
        public readonly float $percentualRegulares,
    ) {
    }

    public function toArray(): array
    {
        return [
            'totalMunicipios' => $this->totalMunicipios,
            'municipiosComPlano' => $this->municipiosComPlano,
            'municipiosSemPlano' => $this->municipiosSemPlano,
            'percentualComPlano' => $this->percentualComPlano,
            'totalPlanos' => $this->totalPlanos,
            'planosRegulares' => $this->planosRegulares,
            'planosIrregulares' => $this->planosIrregulares,
            'percentualRegulares' => $this->percentualRegulares,
        ];
    }
}
