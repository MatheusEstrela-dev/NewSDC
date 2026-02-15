<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Infrastructure\Persistence;

use App\Modules\Plantao\Domain\Entities\Plantao;
use App\Modules\Plantao\Domain\Repositories\PlantaoRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentPlantaoRepository implements PlantaoRepositoryInterface
{
    public function find(int $id): ?Plantao
    {
        return Plantao::find($id);
    }

    public function findAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Plantao::query()->orderBy('data', 'desc');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('plantonista_nome', 'like', "%{$search}%")
                    ->orWhere('data', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['periodo'])) {
            $query->where('periodo', $filters['periodo']);
        }

        if ($perPage === -1) {
            $total = (clone $query)->count();
            $perPage = $total > 0 ? $total : 1;
        }

        return $query->paginate($perPage);
    }

    public function create(array $data): Plantao
    {
        return Plantao::create($data);
    }

    public function update(int $id, array $data): Plantao
    {
        $plantao = Plantao::findOrFail($id);
        $plantao->update($data);
        return $plantao->fresh();
    }

    public function delete(int $id): bool
    {
        return Plantao::findOrFail($id)->delete();
    }

    public function getStatistics(array $filters = []): array
    {
        $query = Plantao::query();

        return [
            'total' => (clone $query)->count(),
            'ativos' => (clone $query)->where('status', 'ATIVO')->count(),
            'finalizados_hoje' => (clone $query)
                ->where('status', 'FINALIZADO')
                ->whereDate('data', now()->toDateString())
                ->count(),
            'equipe_online' => (clone $query)->where('status', 'ATIVO')->count(),
        ];
    }
}
