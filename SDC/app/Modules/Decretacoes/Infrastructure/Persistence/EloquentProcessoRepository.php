<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes\Infrastructure\Persistence;

use App\Modules\Decretacoes\Domain\Entities\Processo;
use App\Modules\Decretacoes\Domain\Repositories\ProcessoRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repository Implementation: Eloquent Processo
 */
class EloquentProcessoRepository implements ProcessoRepositoryInterface
{
    public function findById(int $id): ?Processo
    {
        return Processo::with(['municipios', 'danosHumanos', 'danosMateriais', 'prejuizos'])
                       ->find($id);
    }

    public function create(array $data): Processo
    {
        return Processo::create($data);
    }

    public function update(int $id, array $data): Processo
    {
        $processo = $this->findById($id);
        $processo->update($data);
        return $processo->fresh();
    }

    public function delete(int $id): bool
    {
        $processo = $this->findById($id);
        return $processo ? $processo->delete() : false;
    }

    public function all(): Collection
    {
        return Processo::with(['municipios'])->get();
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Processo::with(['municipios'])
                       ->orderBy('data_entrada', 'desc')
                       ->paginate($perPage);
    }

    public function findAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Processo::query()->with(['municipios']);

        if (isset($filters['search'])) {
            $query->where(function($q) use ($filters) {
                $q->where('n_protocolo_fide', 'like', "%{$filters['search']}%")
                  ->orWhere('analista', 'like', "%{$filters['search']}%")
                  ->orWhere('observacoes', 'like', "%{$filters['search']}%");
            });
        }

        if (isset($filters['processo'])) {
            $query->where('processo', $filters['processo']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['vigencia_status'])) {
            match($filters['vigencia_status']) {
                'vigente' => $query->vigentes(),
                'vencido' => $query->vencidos(),
                'proximo_vencer' => $query->proximosVencer(),
                default => null
            };
        }

        return $query->orderBy('data_entrada', 'desc')->paginate($perPage);
    }

    public function filterBy(array $filters): Collection
    {
        $query = Processo::query()->with(['municipios']);

        if (isset($filters['search'])) {
            $query->where(function($q) use ($filters) {
                $q->where('n_protocolo_fide', 'like', "%{$filters['search']}%")
                  ->orWhere('analista', 'like', "%{$filters['search']}%")
                  ->orWhere('observacoes', 'like', "%{$filters['search']}%");
            });
        }

        if (isset($filters['processo'])) {
            $query->where('processo', $filters['processo']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['vigencia_status'])) {
            match($filters['vigencia_status']) {
                'vigente' => $query->vigentes(),
                'vencido' => $query->vencidos(),
                'proximo_vencer' => $query->proximosVencer(),
                default => null
            };
        }

        return $query->orderBy('data_entrada', 'desc')->get();
    }
}
