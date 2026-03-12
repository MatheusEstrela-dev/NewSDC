<?php

declare(strict_types=1);

namespace App\Modules\Rat\DTO;

class RatStatisticsDTO
{
    public function __construct(
        public readonly int $total,
        public readonly int $hoje,
        public readonly int $esteMes,
        public readonly int $esteAno,
    ) {
    }

    public function toArray(): array
    {
        return [
            'total' => $this->total,
            'hoje' => $this->hoje,
            'esteMes' => $this->esteMes,
            'esteAno' => $this->esteAno,
        ];
    }
}
