<?php

namespace App\Modules\Tdap\Application\UseCases;

use App\Modules\Tdap\Domain\Entities\Movimentacao;
use App\Modules\Tdap\Domain\Repositories\ProductRepositoryInterface;
use App\Modules\Tdap\Domain\Repositories\ProductLoteRepositoryInterface;
use App\Modules\Tdap\Domain\Repositories\MovimentacaoRepositoryInterface;
use App\Modules\Tdap\Domain\ValueObjects\MovimentacaoType;
use Illuminate\Support\Facades\DB;

/**
 * Cria uma saída de estoque seguindo a estratégia FIFO/FEFO
 */
class CreateSaidaEstoqueUseCase
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly ProductLoteRepositoryInterface $loteRepository,
        private readonly MovimentacaoRepositoryInterface $movimentacaoRepository,
    ) {}

    public function execute(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $product = $this->productRepository->findById($data['product_id']);

            if (!$product) {
                throw new \DomainException("Produto não encontrado");
            }

            $quantidadeNecessaria = $data['quantidade'];
            $quantidadeRestante = $quantidadeNecessaria;
            $movimentacoes = [];

            // Obter estratégia de armazenamento
            $estrategia = $product->getEstrategiaArmazenamento();
            $orderBy = $estrategia->getOrderByClause();

            // Buscar lotes disponíveis seguindo a estratégia
            $lotes = $this->loteRepository->findDisponiveisByProduct(
                $data['product_id'],
                $orderBy
            );

            if ($lotes->isEmpty()) {
                throw new \DomainException("Não há estoque disponível para o produto {$product->nome}");
            }

            // Verificar se tem estoque suficiente
            $estoqueTotal = $lotes->sum('quantidade_atual');
            if ($estoqueTotal < $quantidadeNecessaria) {
                throw new \DomainException(
                    "Estoque insuficiente. Disponível: {$estoqueTotal}, Necessário: {$quantidadeNecessaria}"
                );
            }

            // Processar saída por lote (FEFO/FIFO)
            foreach ($lotes as $lote) {
                if ($quantidadeRestante <= 0) {
                    break;
                }

                // Verificar se o lote está vencido
                if ($lote->isVencido()) {
                    throw new \DomainException(
                        "Lote {$lote->numero_lote} está vencido. Data de validade: {$lote->data_validade->format('d/m/Y')}"
                    );
                }

                $quantidadeDoLote = min($quantidadeRestante, $lote->quantidade_atual);

                // Baixar quantidade do lote
                $lote->baixarQuantidade($quantidadeDoLote);

                // Criar movimentação
                $movimentacaoData = [
                    'tipo' => MovimentacaoType::SAIDA,
                    'product_id' => $data['product_id'],
                    'lote_id' => $lote->id,
                    'quantidade' => $quantidadeDoLote,
                    'data_movimentacao' => $data['data_movimentacao'] ?? now(),
                    'origem' => $data['origem'] ?? 'Depósito',
                    'destino' => $data['destino'] ?? null,
                    'solicitante_id' => $data['solicitante_id'] ?? null,
                    'responsavel_id' => $data['responsavel_id'],
                    'documento_referencia' => $data['documento_referencia'] ?? null,
                    'observacoes' => $data['observacoes'] ?? null,
                ];

                $movimentacao = $this->movimentacaoRepository->create($movimentacaoData);
                $movimentacoes[] = $movimentacao;

                $quantidadeRestante -= $quantidadeDoLote;
            }

            return [
                'success' => true,
                'quantidade_total' => $quantidadeNecessaria,
                'movimentacoes' => $movimentacoes,
                'lotes_utilizados' => count($movimentacoes),
            ];
        });
    }
}
