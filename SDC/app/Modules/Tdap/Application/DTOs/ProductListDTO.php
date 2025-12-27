<?php

namespace App\Modules\Tdap\Application\DTOs;

use Illuminate\Pagination\LengthAwarePaginator;

class ProductListDTO
{
    public function __construct(
        public readonly LengthAwarePaginator $products,
        public readonly array $filters,
        public readonly array $statistics,
    ) {}

    public function toArray(): array
    {
        return [
            'products' => $this->products,
            'filters' => $this->filters,
            'statistics' => $this->statistics,
        ];
    }
}
