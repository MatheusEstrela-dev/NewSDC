<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Infrastructure;

use App\Modules\Inventario\Contracts\EquipamentoRepository;
use App\Modules\Inventario\Models\Equipamento;
use Illuminate\Pagination\LengthAwarePaginator;

final class EloquentEquipamentoRepository implements EquipamentoRepository
{
    public function paginate(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = Equipamento::query()
            ->with(['categoria:id,nome', 'usuario:id,name', 'estacao.usuario:id,name'])
            ->withMax('movimentacoes', 'created_at');

        if (! empty($filters['search'])) {
            $term = trim($filters['search']);
            $query->where(static fn ($query) => $query
                ->where('nome', 'ilike', '%'.$term.'%')
                ->orWhere('patrimonio', 'ilike', '%'.$term.'%')
                ->orWhereHas('usuario', static fn ($user) => $user->where('name', 'ilike', '%'.$term.'%')));
        }
        if (! empty($filters['categoria'])) {
            $query->where('categoria_id', (int) $filters['categoria']);
        }
        if (! empty($filters['situacao'])) {
            $query->where('situacao', $filters['situacao']);
        }

        return $query->orderBy('nome')->orderBy('id')->paginate($perPage);
    }

    public function statistics(): array
    {
        $row = Equipamento::query()
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw("SUM(CASE WHEN situacao = 'disponivel' THEN 1 ELSE 0 END) AS disponiveis")
            ->selectRaw("SUM(CASE WHEN situacao = 'em_uso' THEN 1 ELSE 0 END) AS emprestados")
            ->selectRaw("SUM(CASE WHEN situacao = 'manutencao' THEN 1 ELSE 0 END) AS manutencao")
            ->selectRaw("SUM(CASE WHEN situacao = 'baixado' THEN 1 ELSE 0 END) AS baixados")
            ->first();

        return [
            'total' => (int) $row->total,
            'disponiveis' => (int) $row->disponiveis,
            'emprestados' => (int) $row->emprestados,
            'manutencao' => (int) $row->manutencao,
            'baixados' => (int) $row->baixados,
        ];
    }

    public function find(int $id): ?Equipamento
    {
        return Equipamento::with(['categoria', 'estacao', 'usuario', 'movimentacoes'])->find($id);
    }

    public function save(Equipamento $equipamento): Equipamento
    {
        $equipamento->save();
        return $equipamento;
    }
}
