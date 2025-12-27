<?php

namespace App\Modules\Tdap\Infrastructure\Persistence;

use App\Modules\Tdap\Domain\Entities\Recebimento;
use App\Modules\Tdap\Domain\Repositories\RecebimentoRepositoryInterface;
use App\Modules\Tdap\Domain\ValueObjects\RecebimentoStatus;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class EloquentRecebimentoRepository implements RecebimentoRepositoryInterface
{
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Recebimento::query()->with(['itens.product', 'conferidoPor', 'aprovadoPor']);

        // Filtro por status
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filtro por nota fiscal
        if (!empty($filters['nota_fiscal'])) {
            $query->where('nota_fiscal', 'like', "%{$filters['nota_fiscal']}%");
        }

        // Filtro por período
        if (!empty($filters['data_inicio'])) {
            $query->where('data_chegada', '>=', $filters['data_inicio']);
        }

        if (!empty($filters['data_fim'])) {
            $query->where('data_chegada', '<=', $filters['data_fim']);
        }

        // Busca geral
        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('numero_recebimento', 'like', "%{$filters['search']}%")
                  ->orWhere('nota_fiscal', 'like', "%{$filters['search']}%")
                  ->orWhere('placa_veiculo', 'like', "%{$filters['search']}%");
            });
        }

        // Ordenação
        $sortField = $filters['sort_field'] ?? 'data_chegada';
        $sortDirection = $filters['sort_direction'] ?? 'desc';
        $query->orderBy($sortField, $sortDirection);

        return $query->paginate($perPage);
    }

    public function findById(int $id): ?Recebimento
    {
        return Recebimento::with(['itens.product', 'conferidoPor', 'aprovadoPor'])->find($id);
    }

    public function findByNumero(string $numero): ?Recebimento
    {
        return Recebimento::where('numero_recebimento', $numero)
                         ->with(['itens.product', 'conferidoPor', 'aprovadoPor'])
                         ->first();
    }

    public function create(array $data): Recebimento
    {
        return Recebimento::create($data);
    }

    public function update(int $id, array $data): Recebimento
    {
        $recebimento = $this->findById($id);

        if (!$recebimento) {
            throw new \DomainException("Recebimento #{$id} não encontrado");
        }

        $recebimento->update($data);

        return $recebimento->fresh(['itens.product', 'conferidoPor', 'aprovadoPor']);
    }

    public function delete(int $id): bool
    {
        $recebimento = $this->findById($id);

        if (!$recebimento) {
            return false;
        }

        return $recebimento->delete();
    }

    public function findByStatus(RecebimentoStatus $status): Collection
    {
        return Recebimento::where('status', $status->value)
                         ->with(['itens.product'])
                         ->orderBy('data_chegada', 'desc')
                         ->get();
    }

    public function findPendentesConferencia(): Collection
    {
        return Recebimento::whereIn('status', [
            RecebimentoStatus::PENDENTE->value,
            RecebimentoStatus::EM_CONFERENCIA->value,
        ])
        ->with(['itens.product'])
        ->orderBy('data_chegada', 'asc')
        ->get();
    }

    public function getStatistics(array $filters = []): array
    {
        $query = Recebimento::query();

        // Aplicar filtros de período se houver
        if (!empty($filters['data_inicio'])) {
            $query->where('data_chegada', '>=', $filters['data_inicio']);
        }

        if (!empty($filters['data_fim'])) {
            $query->where('data_chegada', '<=', $filters['data_fim']);
        }

        $total = $query->count();

        $porStatus = Recebimento::selectRaw('status, COUNT(*) as count')
                                ->when(!empty($filters['data_inicio']), function($q) use ($filters) {
                                    $q->where('data_chegada', '>=', $filters['data_inicio']);
                                })
                                ->when(!empty($filters['data_fim']), function($q) use ($filters) {
                                    $q->where('data_chegada', '<=', $filters['data_fim']);
                                })
                                ->groupBy('status')
                                ->pluck('count', 'status')
                                ->toArray();

        $pendentes = $porStatus[RecebimentoStatus::PENDENTE->value] ?? 0;
        $emConferencia = $porStatus[RecebimentoStatus::EM_CONFERENCIA->value] ?? 0;
        $finalizados = $porStatus[RecebimentoStatus::FINALIZADO->value] ?? 0;

        return [
            'total' => $total,
            'por_status' => $porStatus,
            'pendentes' => $pendentes,
            'em_conferencia' => $emConferencia,
            'finalizados' => $finalizados,
        ];
    }
}
