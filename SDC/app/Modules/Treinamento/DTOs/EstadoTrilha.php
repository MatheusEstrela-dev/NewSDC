<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\DTOs;

final readonly class EstadoTrilha
{
    /**
     * @param  list<EstadoPasta>  $pastas
     */
    public function __construct(
        public float $percentual,
        public ?int $proximoItemId,
        public int $concluidos,
        public int $total,
        public array $pastas,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'percentual' => $this->percentual,
            'proximo_item_id' => $this->proximoItemId,
            'concluidos' => $this->concluidos,
            'total' => $this->total,
            'pastas' => array_map(fn (EstadoPasta $p) => $p->toArray(), $this->pastas),
        ];
    }
}
