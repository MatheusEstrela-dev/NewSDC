<?php

namespace App\Modules\Tdap\Infrastructure\Persistence;

use App\Modules\Tdap\Domain\Entities\Movimentacao;
use App\Modules\Tdap\Domain\Repositories\MovimentacaoRepositoryInterface;
use App\Modules\Tdap\Domain\ValueObjects\MovimentacaoType;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class EloquentMovimentacaoRepository implements MovimentacaoRepositoryInterface
{
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Movimentacao::query()->with(['product', 'lote', 'solicitante', 'responsavel']);

        // Filtro por tipo
        if (!empty($filters['tipo'])) {
            $query->where('tipo', $filters['tipo']);
        }

        // Filtro por produto
        if (!empty($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }

        // Filtro por período
        if (!empty($filters['data_inicio'])) {
            $query->where('data_movimentacao', '>=', $filters['data_inicio']);
        }

        if (!empty($filters['data_fim'])) {
            $query->where('data_movimentacao', '<=', $filters['data_fim']);
        }

        // Busca
        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('numero_movimentacao', 'like', "%{$filters['search']}%")
                  ->orWhere('documento_referencia', 'like', "%{$filters['search']}%");
            });
        }

        // Ordenação
        $sortField = $filters['sort_field'] ?? 'data_movimentacao';
        $sortDirection = $filters['sort_direction'] ?? 'desc';
        $query->orderBy($sortField, $sortDirection);

        return $query->paginate($perPage);
    }

    public function findById(int $id): ?Movimentacao
    {
        return Movimentacao::with(['product', 'lote', 'solicitante', 'responsavel'])->find($id);
    }

    public function create(array $data): Movimentacao
    {
        return Movimentacao::create($data);
    }

    public function findByProduct(int $productId): Collection
    {
        return Movimentacao::where('product_id', $productId)
                          ->with(['lote', 'solicitante', 'responsavel'])
                          ->orderBy('data_movimentacao', 'desc')
                          ->get();
    }

    public function findByLote(int $loteId): Collection
    {
        return Movimentacao::where('lote_id', $loteId)
                          ->with(['product', 'solicitante', 'responsavel'])
                          ->orderBy('data_movimentacao', 'desc')
                          ->get();
    }

    public function findByTipo(MovimentacaoType $tipo): Collection
    {
        return Movimentacao::where('tipo', $tipo->value)
                          ->with(['product', 'lote'])
                          ->orderBy('data_movimentacao', 'desc')
                          ->get();
    }

    public function findByPeriodo(\DateTime $inicio, \DateTime $fim): Collection
    {
        return Movimentacao::whereBetween('data_movimentacao', [$inicio, $fim])
                          ->with(['product', 'lote'])
                          ->orderBy('data_movimentacao', 'desc')
                          ->get();
    }

    public function getStatistics(array $filters = []): array
    {
        $query = Movimentacao::query();

        if (!empty($filters['data_inicio'])) {
            $query->where('data_movimentacao', '>=', $filters['data_inicio']);
        }

        if (!empty($filters['data_fim'])) {
            $query->where('data_movimentacao', '<=', $filters['data_fim']);
        }

        $total = $query->count();

        $porTipo = Movimentacao::selectRaw('tipo, COUNT(*) as count, SUM(quantidade) as total_quantidade')
                              ->when(!empty($filters['data_inicio']), function($q) use ($filters) {
                                  $q->where('data_movimentacao', '>=', $filters['data_inicio']);
                              })
                              ->when(!empty($filters['data_fim']), function($q) use ($filters) {
                                  $q->where('data_movimentacao', '<=', $filters['data_fim']);
                              })
                              ->groupBy('tipo')
                              ->get()
                              ->keyBy('tipo')
                              ->toArray();

        return [
            'total' => $total,
            'por_tipo' => $porTipo,
            'total_entradas' => $porTipo[MovimentacaoType::ENTRADA->value]['count'] ?? 0,
            'total_saidas' => $porTipo[MovimentacaoType::SAIDA->value]['count'] ?? 0,
            'total_transferencias' => $porTipo[MovimentacaoType::TRANSFERENCIA->value]['count'] ?? 0,
        ];
    }

    public function getHistoricoProduct(int $productId, int $limit = 50): Collection
    {
        return Movimentacao::where('product_id', $productId)
                          ->with(['lote', 'solicitante', 'responsavel'])
                          ->orderBy('data_movimentacao', 'desc')
                          ->limit($limit)
                          ->get();
    }
}
