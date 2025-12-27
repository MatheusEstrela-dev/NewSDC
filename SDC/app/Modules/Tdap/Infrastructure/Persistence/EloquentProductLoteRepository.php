<?php

namespace App\Modules\Tdap\Infrastructure\Persistence;

use App\Modules\Tdap\Domain\Entities\ProductLote;
use App\Modules\Tdap\Domain\Repositories\ProductLoteRepositoryInterface;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class EloquentProductLoteRepository implements ProductLoteRepositoryInterface
{
    public function findById(int $id): ?ProductLote
    {
        return ProductLote::find($id);
    }

    public function create(array $data): ProductLote
    {
        return ProductLote::create($data);
    }

    public function update(int $id, array $data): ProductLote
    {
        $lote = $this->findById($id);

        if (!$lote) {
            throw new \DomainException("Lote #{$id} não encontrado");
        }

        $lote->update($data);

        return $lote->fresh();
    }

    public function findByProduct(int $productId): Collection
    {
        return ProductLote::where('product_id', $productId)
                         ->orderBy('data_entrada', 'desc')
                         ->get();
    }

    public function findDisponiveisByProduct(int $productId, string $orderBy = 'data_entrada ASC'): Collection
    {
        $query = ProductLote::where('product_id', $productId)
                           ->where('quantidade_atual', '>', 0);

        // Parse orderBy string (ex: "data_validade ASC, data_entrada ASC")
        $orderParts = explode(',', $orderBy);
        foreach ($orderParts as $part) {
            $part = trim($part);
            $orderFields = explode(' ', $part);
            $field = $orderFields[0];
            $direction = $orderFields[1] ?? 'ASC';

            // Handle NULLS LAST
            if (strpos($part, 'NULLS LAST') !== false) {
                $query->orderByRaw("$field IS NULL, $field $direction");
            } else {
                $query->orderBy($field, $direction);
            }
        }

        return $query->get();
    }

    public function findVencidos(): Collection
    {
        return ProductLote::whereNotNull('data_validade')
                         ->where('data_validade', '<', Carbon::now())
                         ->where('quantidade_atual', '>', 0)
                         ->with('product')
                         ->get();
    }

    public function findProximosVencimento(int $diasAlerta = 30): Collection
    {
        $dataLimite = Carbon::now()->addDays($diasAlerta);

        return ProductLote::whereNotNull('data_validade')
                         ->where('data_validade', '<=', $dataLimite)
                         ->where('data_validade', '>=', Carbon::now())
                         ->where('quantidade_atual', '>', 0)
                         ->with('product')
                         ->orderBy('data_validade', 'asc')
                         ->get();
    }

    public function findMelhorLoteParaSaida(int $productId, int $quantidadeNecessaria): ?ProductLote
    {
        // Busca o primeiro lote disponível seguindo a estratégia FEFO/FIFO
        $product = \App\Modules\Tdap\Domain\Entities\Product::find($productId);

        if (!$product) {
            return null;
        }

        $estrategia = $product->getEstrategiaArmazenamento();
        $orderBy = $estrategia->getOrderByClause();

        return $this->findDisponiveisByProduct($productId, $orderBy)->first();
    }

    public function calcularEstoqueTotal(int $productId): int
    {
        return ProductLote::where('product_id', $productId)
                         ->sum('quantidade_atual');
    }
}
