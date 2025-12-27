<?php

namespace App\Modules\Tdap\Infrastructure\Persistence;

use App\Modules\Tdap\Domain\Entities\Product;
use App\Modules\Tdap\Domain\Repositories\ProductRepositoryInterface;
use App\Modules\Tdap\Domain\ValueObjects\ProductType;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class EloquentProductRepository implements ProductRepositoryInterface
{
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Product::query();

        // Filtro por tipo
        if (!empty($filters['tipo'])) {
            $query->where('tipo', $filters['tipo']);
        }

        // Filtro por grupo de risco
        if (!empty($filters['grupo_risco'])) {
            $query->where('grupo_risco', $filters['grupo_risco']);
        }

        // Filtro por produtos compostos
        if (isset($filters['eh_composto'])) {
            $query->where('eh_composto', $filters['eh_composto']);
        }

        // Busca por nome ou código
        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('nome', 'like', "%{$filters['search']}%")
                  ->orWhere('codigo', 'like', "%{$filters['search']}%");
            });
        }

        // Ordenação
        $sortField = $filters['sort_field'] ?? 'nome';
        $sortDirection = $filters['sort_direction'] ?? 'asc';
        $query->orderBy($sortField, $sortDirection);

        return $query->paginate($perPage);
    }

    public function findById(int $id): ?Product
    {
        return Product::find($id);
    }

    public function findByCodigo(string $codigo): ?Product
    {
        return Product::where('codigo', $codigo)->first();
    }

    public function create(array $data): Product
    {
        return Product::create($data);
    }

    public function update(int $id, array $data): Product
    {
        $product = $this->findById($id);

        if (!$product) {
            throw new \DomainException("Produto #{$id} não encontrado");
        }

        $product->update($data);

        return $product->fresh();
    }

    public function delete(int $id): bool
    {
        $product = $this->findById($id);

        if (!$product) {
            return false;
        }

        return $product->delete();
    }

    public function findByTipo(ProductType $tipo): Collection
    {
        return Product::where('tipo', $tipo->value)->get();
    }

    public function findComEstoqueBaixo(): Collection
    {
        return Product::query()
            ->whereHas('lotes', function ($query) {
                $query->selectRaw('product_id, SUM(quantidade_atual) as total')
                      ->groupBy('product_id');
            })
            ->whereRaw('(SELECT SUM(quantidade_atual) FROM tdap_product_lotes WHERE product_id = tdap_products.id) <= estoque_minimo')
            ->get();
    }

    public function findPerecíveis(): Collection
    {
        return Product::whereIn('tipo', [
            ProductType::CESTA_BASICA->value,
            ProductType::KIT_LIMPEZA->value,
        ])->get();
    }

    public function findCompostos(): Collection
    {
        return Product::where('eh_composto', true)->with('composicao.produtoComponente')->get();
    }

    public function getStatistics(): array
    {
        $total = Product::count();
        $porTipo = Product::selectRaw('tipo, COUNT(*) as count')
                          ->groupBy('tipo')
                          ->pluck('count', 'tipo')
                          ->toArray();

        $compostos = Product::where('eh_composto', true)->count();
        $pereciveis = Product::whereIn('tipo', [
            ProductType::CESTA_BASICA->value,
            ProductType::KIT_LIMPEZA->value,
        ])->count();

        return [
            'total' => $total,
            'por_tipo' => $porTipo,
            'compostos' => $compostos,
            'pereciveis' => $pereciveis,
        ];
    }
}
