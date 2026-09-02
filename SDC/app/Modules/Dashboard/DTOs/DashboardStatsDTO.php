<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\DTOs;

class DashboardStatsDTO
{
    public function __construct(
        public readonly int   $ratAbertas,
        public readonly int   $paeEmAnalise,
        public readonly int   $decretosAprovados,
        public readonly int   $demandasConcluidas,
        public readonly float $ratTrend,
        public readonly float $paeTrend,
        public readonly float $decretoTrend,
        public readonly float $demandaTrend,
        public readonly array $moduleDistribution,
        public readonly array $barData6M,
        public readonly array $barData12M,
        public readonly array $sparklines,
        public readonly array $planConStats,
        // Situacao da frota do plantao. Fica na Visao Geral porque "quantas
        // viaturas estao disponiveis agora" e pergunta de quem nem entra no
        // modulo de Plantao -- e a informacao mais consultada da frota.
        public readonly array $frotaStats,
    ) {}

    public function toArray(): array
    {
        return [
            'ratAbertas'         => $this->ratAbertas,
            'paeEmAnalise'       => $this->paeEmAnalise,
            'decretosAprovados'  => $this->decretosAprovados,
            'demandasConcluidas' => $this->demandasConcluidas,
            'ratTrend'           => $this->ratTrend,
            'paeTrend'           => $this->paeTrend,
            'decretoTrend'       => $this->decretoTrend,
            'demandaTrend'       => $this->demandaTrend,
            'moduleDistribution' => $this->moduleDistribution,
            'barData6M'          => $this->barData6M,
            'barData12M'         => $this->barData12M,
            'sparklines'         => $this->sparklines,
            'planConStats'       => $this->planConStats,
            'frotaStats'         => $this->frotaStats,
        ];
    }
}
