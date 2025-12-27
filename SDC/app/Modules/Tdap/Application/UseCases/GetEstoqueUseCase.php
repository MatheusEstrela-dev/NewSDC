<?php

namespace App\Modules\Tdap\Application\UseCases;

use App\Modules\Tdap\Application\DTOs\EstoqueDTO;
use App\Modules\Tdap\Domain\Repositories\ProductRepositoryInterface;
use App\Modules\Tdap\Domain\Repositories\ProductLoteRepositoryInterface;

class GetEstoqueUseCase
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly ProductLoteRepositoryInterface $loteRepository,
    ) {}

    public function execute(int $productId): EstoqueDTO
    {
        $product = $this->productRepository->findById($productId);

        if (!$product) {
            throw new \DomainException("Produto não encontrado");
        }

        $lotes = $this->loteRepository->findByProduct($productId);

        $quantidadeTotal = $lotes->sum('quantidade_atual');
        $quantidadeDisponivel = $lotes->filter(fn($lote) => !$lote->isVencido())->sum('quantidade_atual');
        $quantidadeReservada = 0; // TODO: Implementar sistema de reserva

        // Gerar alertas
        $alertas = [];

        // Alerta de estoque baixo
        if ($product->precisaRessuprimento($quantidadeDisponivel)) {
            $alertas[] = [
                'tipo' => 'estoque_baixo',
                'mensagem' => "Estoque abaixo do mínimo ({$product->estoque_minimo})",
                'severidade' => 'warning',
            ];
        }

        // Alertas de validade
        foreach ($lotes as $lote) {
            if ($lote->isVencido()) {
                $alertas[] = [
                    'tipo' => 'vencido',
                    'mensagem' => "Lote {$lote->numero_lote} vencido em {$lote->data_validade->format('d/m/Y')}",
                    'severidade' => 'danger',
                ];
            } elseif ($lote->isProximoVencimento($product->dias_alerta_validade ?? 30)) {
                $diasRestantes = $lote->diasAteVencimento();
                $alertas[] = [
                    'tipo' => 'proximo_vencimento',
                    'mensagem' => "Lote {$lote->numero_lote} vence em {$diasRestantes} dias",
                    'severidade' => 'warning',
                ];
            }
        }

        return new EstoqueDTO(
            productId: $product->id,
            productNome: $product->nome,
            quantidadeTotal: $quantidadeTotal,
            quantidadeDisponivel: $quantidadeDisponivel,
            quantidadeReservada: $quantidadeReservada,
            lotes: $lotes->map(fn($lote) => [
                'id' => $lote->id,
                'numero_lote' => $lote->numero_lote,
                'quantidade_atual' => $lote->quantidade_atual,
                'data_validade' => $lote->data_validade?->format('Y-m-d'),
                'dias_ate_vencimento' => $lote->diasAteVencimento(),
                'is_vencido' => $lote->isVencido(),
                'localizacao' => $lote->localizacao,
            ])->toArray(),
            precisaRessuprimento: $product->precisaRessuprimento($quantidadeDisponivel),
            alertas: $alertas,
        );
    }
}
