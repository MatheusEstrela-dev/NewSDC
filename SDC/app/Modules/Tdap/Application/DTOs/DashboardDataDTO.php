<?php

namespace App\Modules\Tdap\Application\DTOs;

readonly class DashboardDataDTO
{
    public function __construct(
        public array $statistics,
        public array $alertas,
        public int $produtosEstoqueBaixo,
        public int $lotesVencidos,
        public int $lotesProximosVencimento,
        public int $recebimentosPendentes,
    ) {}

    public function toArray(): array
    {
        return [
            'statistics' => $this->statistics,
            'alertas' => $this->alertas,
        ];
    }
}
