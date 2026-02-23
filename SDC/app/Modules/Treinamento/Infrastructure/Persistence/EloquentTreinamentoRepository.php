<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Infrastructure\Persistence;

use App\Modules\Treinamento\Domain\Entities\Treinamento;
use App\Modules\Treinamento\Domain\Repositories\TreinamentoRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentTreinamentoRepository implements TreinamentoRepositoryInterface
{
    public function find(int $id): ?Treinamento
    {
        return Treinamento::with(['modulos', 'inscricoes', 'createdBy'])
            ->find($id);
    }

    public function findAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Treinamento::query();

        // Eager loading
        $query->with(['modulos', 'inscricoesAprovadas', 'createdBy']);

        // Filtro por status
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filtro por tipo
        if (!empty($filters['tipo'])) {
            $query->where('tipo', $filters['tipo']);
        }

        // Busca por título ou instrutor
        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('titulo', 'like', "%{$filters['search']}%")
                    ->orWhere('instrutor', 'like', "%{$filters['search']}%");
            });
        }

        // Ordenação
        $sortField = $filters['sort_field'] ?? 'created_at';
        $sortDirection = $filters['sort_direction'] ?? 'desc';
        $query->orderBy($sortField, $sortDirection);

        if ($perPage === -1) {
            $total = (clone $query)->count();
            $perPage = $total > 0 ? $total : 1;
        }

        return $query->paginate($perPage);
    }

    public function create(array $data): Treinamento
    {
        return Treinamento::create($data);
    }

    public function update(int $id, array $data): Treinamento
    {
        $treinamento = $this->find($id);

        if (!$treinamento) {
            throw new \RuntimeException("Treinamento não encontrado");
        }

        $treinamento->update($data);
        $treinamento->refresh();

        return $treinamento;
    }

    public function delete(int $id): bool
    {
        $treinamento = $this->find($id);

        if (!$treinamento) {
            return false;
        }

        return $treinamento->delete();
    }

    public function getStatistics(array $filters = []): array
    {
        $query = Treinamento::query();

        // Aplicar mesmos filtros se houver
        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('titulo', 'like', "%{$filters['search']}%")
                    ->orWhere('instrutor', 'like', "%{$filters['search']}%");
            });
        }

        $total = (clone $query)->count();
        $planejados = (clone $query)->where('status', 'PLANEJADO')->count();
        $emAndamento = (clone $query)->where('status', 'EM_ANDAMENTO')->count();
        $concluidos = (clone $query)->where('status', 'CONCLUIDO')->count();
        $cancelados = (clone $query)->where('status', 'CANCELADO')->count();

        return [
            'total' => $total,
            'planejados' => $planejados,
            'em_andamento' => $emAndamento,
            'concluidos' => $concluidos,
            'cancelados' => $cancelados,
        ];
    }
}
