<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Services;

use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Shared\BaseService;
use Illuminate\Pagination\LengthAwarePaginator;

class TreinamentoService extends BaseService
{
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Treinamento::query()->with(['modulos', 'inscricoes']);

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('titulo', 'like', "%{$filters['search']}%")
                  ->orWhere('descricao', 'like', "%{$filters['search']}%");
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['tipo'])) {
            $query->where('tipo', $filters['tipo']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function findById(int $id): ?Treinamento
    {
        return Treinamento::with(['modulos', 'inscricoes', 'frequencias'])->find($id);
    }

    public function create(array $data): Treinamento
    {
        return Treinamento::create($data);
    }

    public function update(int $id, array $data): bool
    {
        $treinamento = Treinamento::find($id);
        if (!$treinamento) {
            return false;
        }
        return $treinamento->update($data);
    }

    public function delete(int $id): bool
    {
        $treinamento = Treinamento::find($id);
        if (!$treinamento) {
            return false;
        }
        return $treinamento->delete();
    }

    public function getStatistics(): array
    {
        return [
            'total' => Treinamento::count(),
            'ativos' => Treinamento::where('status', 'ativo')->count(),
            'concluidos' => Treinamento::where('status', 'concluido')->count(),
        ];
    }

    public function export(array $filters = []): array
    {
        $query = Treinamento::query();

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->get()->toArray();
    }
}
