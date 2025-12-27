<?php

namespace App\Modules\Tdap\Domain\Repositories;

use App\Modules\Tdap\Domain\Entities\Product;
use App\Modules\Tdap\Domain\ValueObjects\ProductType;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface ProductRepositoryInterface
{
    /**
     * Lista produtos com filtros e paginação
     */
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Encontra produto por ID
     */
    public function findById(int $id): ?Product;

    /**
     * Encontra produto por código
     */
    public function findByCodigo(string $codigo): ?Product;

    /**
     * Cria novo produto
     */
    public function create(array $data): Product;

    /**
     * Atualiza produto
     */
    public function update(int $id, array $data): Product;

    /**
     * Remove produto (soft delete)
     */
    public function delete(int $id): bool;

    /**
     * Lista produtos por tipo
     */
    public function findByTipo(ProductType $tipo): Collection;

    /**
     * Lista produtos com estoque abaixo do mínimo
     */
    public function findComEstoqueBaixo(): Collection;

    /**
     * Lista produtos perecíveis
     */
    public function findPerecíveis(): Collection;

    /**
     * Lista produtos compostos (kits)
     */
    public function findCompostos(): Collection;

    /**
     * Obtém estatísticas de produtos
     */
    public function getStatistics(): array;
}
