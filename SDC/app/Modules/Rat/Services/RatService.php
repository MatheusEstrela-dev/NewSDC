<?php

declare(strict_types=1);

namespace App\Modules\Rat\Services;

use App\Modules\Rat\Models\Rat;
use App\Modules\Shared\BaseService;
use Illuminate\Pagination\LengthAwarePaginator;

class RatService extends BaseService
{
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Rat::query();

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('numero_protocolo', 'like', "%{$filters['search']}%")
                  ->orWhere('municipio', 'like', "%{$filters['search']}%");
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function findById(int $id): ?Rat
    {
        return Rat::find($id);
    }

    public function findByIdAsJson(int $id): ?array
    {
        $rat = $this->findById($id);
        return $rat ? $rat->toArray() : null;
    }

    public function create(array $data): Rat
    {
        return Rat::create($data);
    }

    public function update(int $id, array $data): bool
    {
        $rat = Rat::find($id);
        if (!$rat) {
            return false;
        }
        return $rat->update($data);
    }

    public function delete(int $id): bool
    {
        $rat = Rat::find($id);
        if (!$rat) {
            return false;
        }
        return $rat->delete();
    }

    public function finalize(int $id): bool
    {
        $rat = Rat::find($id);
        if (!$rat) {
            return false;
        }
        return $rat->update(['status' => 'finalizado']);
    }

    public function getStatistics(): array
    {
        return [
            'total' => Rat::count(),
            'em_andamento' => Rat::where('status', 'em_andamento')->count(),
            'finalizados' => Rat::where('status', 'finalizado')->count(),
        ];
    }

    public function sync(): void
    {
        // Sync logic with external API
    }

    public function export(array $filters = []): array
    {
        $query = Rat::query();

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->get()->toArray();
    }
}
