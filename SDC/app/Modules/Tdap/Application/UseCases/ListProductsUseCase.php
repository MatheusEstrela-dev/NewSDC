<?php

namespace App\Modules\Tdap\Application\UseCases;

use App\Modules\Tdap\Application\DTOs\ProductListDTO;
use App\Modules\Tdap\Domain\Repositories\ProductRepositoryInterface;

class ListProductsUseCase
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository
    ) {}

    public function execute(array $filters = [], int $perPage = 15): ProductListDTO
    {
        $products = $this->productRepository->list($filters, $perPage);
        $statistics = $this->productRepository->getStatistics();

        return new ProductListDTO(
            products: $products,
            filters: $filters,
            statistics: $statistics,
        );
    }

    /**
     * Executa e retorna dados serializados para Inertia
     * (Paginator convertido em array compatível com JSON)
     */
    public function executeAsDTO(array $filters = [], int $perPage = 15): array
    {
        $paginator = $this->productRepository->list($filters, $perPage);
        $statistics = $this->productRepository->getStatistics();

        return [
            'data' => collect($paginator->items())
                ->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'codigo' => $product->codigo,
                        'nome' => $product->nome,
                        'descricao' => $product->descricao,
                        'categoria' => $product->categoria,
                        'unidade_medida' => $product->unidade_medida,
                        'estoque_minimo' => $product->estoque_minimo,
                        'estoque_maximo' => $product->estoque_maximo,
                        'estoque_atual' => $product->estoque_atual,
                        'preco_unitario' => $product->preco_unitario,
                        'localizacao_padrao' => $product->localizacao_padrao,
                        'is_active' => $product->is_active,
                        'created_at' => $product->created_at?->toIso8601String(),
                        'updated_at' => $product->updated_at?->toIso8601String(),
                    ];
                })
                ->toArray(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'filters' => $filters,
            'statistics' => $statistics,
        ];
    }
}
