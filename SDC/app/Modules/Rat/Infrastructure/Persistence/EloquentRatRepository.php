<?php

namespace App\Modules\Rat\Infrastructure\Persistence;

use App\Modules\Rat\Domain\Entities\Rat;
use App\Modules\Rat\Domain\Repositories\RatRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EloquentRatRepository implements RatRepositoryInterface
{
    public function find(int $id): ?Rat
    {
        return Rat::find($id);
    }

    public function findAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        // Por enquanto, retornar dados mockados
        // TODO: Implementar quando a migration for criada
        $rats = collect([
            [
                'id' => 1,
                'protocolo' => 'RAT-2024-001',
                'status' => 'em_andamento',
                'created_at' => now()->subDays(2),
                'updated_at' => now()->subHours(5),
                'local' => ['municipio' => 'Belo Horizonte/MG'],
                'dados_gerais' => ['data_fato' => now()->subDays(3)],
                'created_by' => 1,
            ],
            [
                'id' => 2,
                'protocolo' => 'RAT-2024-002',
                'status' => 'rascunho',
                'created_at' => now()->subDays(1),
                'updated_at' => now()->subHours(2),
                'local' => ['municipio' => 'Contagem/MG'],
                'dados_gerais' => ['data_fato' => now()->subDays(1)],
                'created_by' => 1,
            ],
        ]);

        // Controller/UseCases esperam objetos (ex.: $rat->id). No mock, converter arrays em objetos.
        $rats = $rats->map(static fn (array $rat) => (object) $rat);

        // Simular paginação
        $currentPage = request()->get('page', 1);
        $perPage = 15;
        $total = $rats->count();
        $items = $rats->forPage($currentPage, $perPage);

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $currentPage,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        /*
         * TODO: Quando a migration for criada, usar:
         *
         * $query = Rat::query();
         *
         * // Aplicar filtros
         * if (isset($filters['protocolo']) && $filters['protocolo']) {
         *     $query->where('protocolo', 'like', '%' . $filters['protocolo'] . '%');
         * }
         *
         * if (isset($filters['status']) && $filters['status']) {
         *     $query->where('status', $filters['status']);
         * }
         *
         * if (isset($filters['data_inicio']) && $filters['data_inicio']) {
         *     $query->whereDate('created_at', '>=', $filters['data_inicio']);
         * }
         *
         * if (isset($filters['data_fim']) && $filters['data_fim']) {
         *     $query->whereDate('created_at', '<=', $filters['data_fim']);
         * }
         *
         * if (isset($filters['ano']) && $filters['ano']) {
         *     $query->whereYear('created_at', $filters['ano']);
         * }
         *
         * return $query->orderBy('created_at', 'desc')->paginate($perPage);
         */
    }

    public function create(array $data): Rat
    {
        // TODO: Implementar quando a migration for criada
        throw new \Exception('Método não implementado ainda');
    }

    public function update(int $id, array $data): Rat
    {
        // TODO: Implementar quando a migration for criada
        throw new \Exception('Método não implementado ainda');
    }

    public function delete(int $id): bool
    {
        // TODO: Implementar quando a migration for criada
        return false;
    }

    public function getStatistics(array $filters = []): array
    {
        // Por enquanto, retornar estatísticas mockadas
        // TODO: Implementar quando a migration for criada
        $rats = collect([
            ['created_at' => now()->subDays(2)],
            ['created_at' => now()->subDays(1)],
            ['created_at' => now()->subHours(5)],
        ]);

        $total = $rats->count();
        $hoje = $rats->filter(fn ($r) => date('Y-m-d', strtotime($r['created_at'])) === date('Y-m-d'))->count();
        $esteMes = $rats->filter(fn ($r) => date('Y-m', strtotime($r['created_at'])) === date('Y-m'))->count();
        $esteAno = $rats->filter(fn ($r) => date('Y', strtotime($r['created_at'])) === date('Y'))->count();

        return [
            'total' => $total,
            'hoje' => $hoje,
            'este_mes' => $esteMes,
            'este_ano' => $esteAno,
        ];

        /*
         * TODO: Quando a migration for criada, usar:
         *
         * $query = Rat::query();
         *
         * // Aplicar filtros se houver
         * if (isset($filters['ano']) && $filters['ano']) {
         *     $query->whereYear('created_at', $filters['ano']);
         * }
         *
         * $total = (clone $query)->count();
         * $hoje = (clone $query)->whereDate('created_at', today())->count();
         * $esteMes = (clone $query)->whereMonth('created_at', now()->month)
         *     ->whereYear('created_at', now()->year)->count();
         * $esteAno = (clone $query)->whereYear('created_at', now()->year)->count();
         *
         * return [
         *     'total' => $total,
         *     'hoje' => $hoje,
         *     'este_mes' => $esteMes,
         *     'este_ano' => $esteAno,
         * ];
         */
    }
}


