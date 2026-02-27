<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes\Services;

use App\Modules\Decretacoes\Models\Processo;
use App\Modules\Shared\BaseService;
use Illuminate\Pagination\LengthAwarePaginator;

class ProcessoService extends BaseService
{
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Processo::query()->with(['anexos', 'logs']);

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('numero_processo', 'like', "%{$filters['search']}%")
                  ->orWhere('municipio', 'like', "%{$filters['search']}%");
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['tipo_decreto'])) {
            $query->where('tipo_decreto', $filters['tipo_decreto']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function findById(int $id): ?Processo
    {
        return Processo::with(['anexos', 'danosHumanos', 'danosMateriais', 'prejuizos', 'logs'])->find($id);
    }

    public function create(array $data): Processo
    {
        return Processo::create($data);
    }

    public function update(int $id, array $data): bool
    {
        $processo = Processo::find($id);
        if (!$processo) {
            return false;
        }
        return $processo->update($data);
    }

    public function delete(int $id): bool
    {
        $processo = Processo::find($id);
        if (!$processo) {
            return false;
        }
        return $processo->delete();
    }

    public function getStatistics(): array
    {
        return [
            'total' => Processo::count(),
            'em_analise' => Processo::where('status', 'em_analise')->count(),
            'aprovados' => Processo::where('status', 'aprovado')->count(),
            'rejeitados' => Processo::where('status', 'rejeitado')->count(),
        ];
    }
}
