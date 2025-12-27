<?php

namespace App\Modules\Tdap\Application\DTOs;

class EstoqueDTO
{
    public function __construct(
        public readonly int $productId,
        public readonly string $productNome,
        public readonly int $quantidadeTotal,
        public readonly int $quantidadeDisponivel,
        public readonly int $quantidadeReservada,
        public readonly array $lotes,
        public readonly bool $precisaRessuprimento,
        public readonly array $alertas,
    ) {}

    public function toArray(): array
    {
        return [
            'product_id' => $this->productId,
            'product_nome' => $this->productNome,
            'quantidade_total' => $this->quantidadeTotal,
            'quantidade_disponivel' => $this->quantidadeDisponivel,
            'quantidade_reservada' => $this->quantidadeReservada,
            'lotes' => $this->lotes,
            'precisa_ressuprimento' => $this->precisaRessuprimento,
            'alertas' => $this->alertas,
        ];
    }
}
