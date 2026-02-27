<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Services;

use App\Modules\Tdap\Models\Product;
use App\Modules\Tdap\Models\Recebimento;
use App\Modules\Tdap\Models\Movimentacao;
use App\Modules\Shared\BaseService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class TdapService extends BaseService
{
    public function listProducts(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Product::query();

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('nome', 'like', "%{$filters['search']}%")
                  ->orWhere('codigo', 'like', "%{$filters['search']}%");
            });
        }

        if (isset($filters['ativo'])) {
            $query->where('ativo', $filters['ativo']);
        }

        return $query->orderBy('nome')->paginate($perPage);
    }

    public function findProductById(int $id): ?Product
    {
        return Product::with(['recebimentos', 'movimentacoes'])->find($id);
    }

    public function createProduct(array $data): Product
    {
        return Product::create($data);
    }

    public function getProductEstoque(int $productId): array
    {
        $product = Product::find($productId);
        if (!$product) {
            return [];
        }

        $entradas = Movimentacao::where('product_id', $productId)
            ->where('tipo', 'entrada')
            ->sum('quantidade');

        $saidas = Movimentacao::where('product_id', $productId)
            ->where('tipo', 'saida')
            ->sum('quantidade');

        return [
            'product' => $product,
            'entradas' => $entradas,
            'saidas' => $saidas,
            'saldo' => $entradas - $saidas,
        ];
    }

    public function listRecebimentos(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Recebimento::query()->with('product');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function findRecebimentoById(int $id): ?Recebimento
    {
        return Recebimento::with('product')->find($id);
    }

    public function createRecebimento(array $data): Recebimento
    {
        return Recebimento::create($data);
    }

    public function processarRecebimento(int $id): bool
    {
        $recebimento = Recebimento::find($id);
        if (!$recebimento || $recebimento->status !== 'pendente') {
            return false;
        }

        Movimentacao::create([
            'product_id' => $recebimento->product_id,
            'tipo' => 'entrada',
            'quantidade' => $recebimento->quantidade,
            'data_movimentacao' => now(),
            'motivo' => 'Recebimento processado',
            'user_id' => auth()->id(),
        ]);

        return $recebimento->update(['status' => 'processado']);
    }

    public function listMovimentacoes(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Movimentacao::query()->with('product');

        if (!empty($filters['tipo'])) {
            $query->where('tipo', $filters['tipo']);
        }

        if (!empty($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function createSaida(array $data): Movimentacao
    {
        $data['tipo'] = 'saida';
        $data['data_movimentacao'] = now();
        $data['user_id'] = auth()->id();

        return Movimentacao::create($data);
    }

    public function getHistoricoMovimentacoes(int $productId): Collection
    {
        return Movimentacao::where('product_id', $productId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getDashboardStatistics(): array
    {
        return [
            'total_produtos' => Product::where('ativo', true)->count(),
            'recebimentos_pendentes' => Recebimento::where('status', 'pendente')->count(),
            'movimentacoes_hoje' => Movimentacao::whereDate('created_at', today())->count(),
        ];
    }
}
