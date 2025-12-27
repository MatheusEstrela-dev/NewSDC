<?php

namespace App\Modules\Tdap\Application\DTOs;

class MovimentacaoStatisticsDTO
{
    public function __construct(
        public readonly int $totalMovimentacoes,
        public readonly int $totalEntradas,
        public readonly int $totalSaidas,
        public readonly int $totalTransferencias,
        public readonly array $movimentacoesPorTipo,
        public readonly array $movimentacoesPorPeriodo,
        public readonly array $produtosMaisMovimentados,
    ) {}

    public function toArray(): array
    {
        return [
            'total_movimentacoes' => $this->totalMovimentacoes,
            'total_entradas' => $this->totalEntradas,
            'total_saidas' => $this->totalSaidas,
            'total_transferencias' => $this->totalTransferencias,
            'movimentacoes_por_tipo' => $this->movimentacoesPorTipo,
            'movimentacoes_por_periodo' => $this->movimentacoesPorPeriodo,
            'produtos_mais_movimentados' => $this->produtosMaisMovimentados,
        ];
    }
}
