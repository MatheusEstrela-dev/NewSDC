<?php

declare(strict_types=1);

namespace App\Modules\Compdec\Services;

use App\Modules\Compdec\Models\Orgao;
use App\Modules\Shared\BaseService;
use Illuminate\Pagination\LengthAwarePaginator;

class OrgaoService extends BaseService
{
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Orgao::query();

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('nome', 'like', "%{$filters['search']}%")
                  ->orWhere('codigo', 'like', "%{$filters['search']}%");
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['tipo'])) {
            $query->where('tipo', $filters['tipo']);
        }

        return $query->orderBy('nome')->paginate($perPage);
    }

    public function findById(int $id): ?Orgao
    {
        return Orgao::find($id);
    }

    public function create(array $data): Orgao
    {
        return Orgao::create($data);
    }

    public function update(int $id, array $data): bool
    {
        $orgao = $this->findById($id);
        if (!$orgao) {
            return false;
        }
        return $orgao->update($data);
    }

    public function delete(int $id): bool
    {
        $orgao = $this->findById($id);
        if (!$orgao) {
            return false;
        }
        return $orgao->delete();
    }

    public function getStatistics(): array
    {
        return [
            'total' => Orgao::count(),
            'ativos' => Orgao::where('status', 'ativo')->count(),
            'inativos' => Orgao::where('status', 'inativo')->count(),
        ];
    }
}
