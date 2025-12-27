<?php

namespace App\Modules\Tdap\Application\DTOs;

use Illuminate\Pagination\LengthAwarePaginator;

class RecebimentoListDTO
{
    public function __construct(
        public readonly LengthAwarePaginator $recebimentos,
        public readonly array $filters,
        public readonly array $statistics,
    ) {}

    public function toArray(): array
    {
        return [
            'recebimentos' => $this->recebimentos,
            'filters' => $this->filters,
            'statistics' => $this->statistics,
        ];
    }
}
