<?php

namespace App\Modules\Tdap\Application\DTOs;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

readonly class MovimentacaoListDTO
{
    public function __construct(
        public LengthAwarePaginator $movimentacoes,
        public array $filters,
        public array $statistics,
    ) {}

    public function toArray(): array
    {
        return [
            'movimentacoes' => $this->movimentacoes,
            'filters' => $this->filters,
            'statistics' => $this->statistics,
        ];
    }
}
