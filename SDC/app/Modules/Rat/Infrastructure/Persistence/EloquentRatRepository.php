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
        // Tentar buscar do banco primeiro (se a tabela tiver dados via RatMockSeeder)
        try {
            $count = Rat::count();
            if ($count > 0) {
                $query = Rat::query();

                if (!empty($filters['search'])) {
                    $search = $filters['search'];
                    $query->where(function ($q) use ($search) {
                        $q->where('protocolo', 'like', "%{$search}%")
                          ->orWhere('status', 'like', "%{$search}%")
                          ->orWhere('local->municipio', 'like', "%{$search}%");
                    });
                }

                if (!empty($filters['status'])) {
                    $query->where('status', $filters['status']);
                }

                if (!empty($filters['ano'])) {
                    $query->whereYear('created_at', $filters['ano']);
                }

                $effectivePerPage = ($perPage === -1) ? max($count, 1) : $perPage;

                return $query->orderBy('created_at', 'desc')->paginate($effectivePerPage);
            }
        } catch (\Exception $e) {
            // Tabela não existe ou erro — usar mock abaixo
        }

        // Fallback: dados mockados em memória
        $rats = collect([
            ['id' => 'mock-001', 'protocolo' => 'RAT-2024-001', 'status' => 'em_andamento', 'created_at' => now()->subDays(15), 'updated_at' => now()->subDays(14), 'local' => ['municipio' => 'Belo Horizonte', 'uf' => 'MG'], 'dados_gerais' => ['tipo_desastre' => 'Inundação', 'data_fato' => now()->subDays(15)->toDateString()], 'created_by' => 1],
            ['id' => 'mock-002', 'protocolo' => 'RAT-2024-002', 'status' => 'em_andamento', 'created_at' => now()->subDays(10), 'updated_at' => now()->subDays(9), 'local' => ['municipio' => 'Contagem', 'uf' => 'MG'], 'dados_gerais' => ['tipo_desastre' => 'Deslizamento', 'data_fato' => now()->subDays(10)->toDateString()], 'created_by' => 2],
            ['id' => 'mock-003', 'protocolo' => 'RAT-2024-003', 'status' => 'em_andamento', 'created_at' => now()->subDays(5), 'updated_at' => now()->subDays(4), 'local' => ['municipio' => 'Uberlândia', 'uf' => 'MG'], 'dados_gerais' => ['tipo_desastre' => 'Vendaval', 'data_fato' => now()->subDays(5)->toDateString()], 'created_by' => 3],
            ['id' => 'mock-004', 'protocolo' => 'RAT-2024-004', 'status' => 'em_andamento', 'created_at' => now()->subDays(3), 'updated_at' => now()->subDays(2), 'local' => ['municipio' => 'Juiz de Fora', 'uf' => 'MG'], 'dados_gerais' => ['tipo_desastre' => 'Enxurrada', 'data_fato' => now()->subDays(3)->toDateString()], 'created_by' => 4],
            ['id' => 'mock-005', 'protocolo' => 'RAT-2024-005', 'status' => 'rascunho', 'created_at' => now()->subDays(2), 'updated_at' => now()->subDay(), 'local' => ['municipio' => 'Gov. Valadares', 'uf' => 'MG'], 'dados_gerais' => ['tipo_desastre' => 'Alagamento', 'data_fato' => now()->subDays(2)->toDateString()], 'created_by' => 5],
            ['id' => 'mock-006', 'protocolo' => 'RAT-2024-006', 'status' => 'rascunho', 'created_at' => now()->subDay(), 'updated_at' => now(), 'local' => ['municipio' => 'Montes Claros', 'uf' => 'MG'], 'dados_gerais' => ['tipo_desastre' => 'Seca', 'data_fato' => now()->subDays(30)->toDateString()], 'created_by' => 6],
            ['id' => 'mock-007', 'protocolo' => 'RAT-2024-007', 'status' => 'rascunho', 'created_at' => now(), 'updated_at' => now(), 'local' => ['municipio' => 'Ipatinga', 'uf' => 'MG'], 'dados_gerais' => ['tipo_desastre' => 'Incêndio Florestal', 'data_fato' => now()->subDay()->toDateString()], 'created_by' => 3],
            ['id' => 'mock-008', 'protocolo' => 'RAT-2024-008', 'status' => 'finalizado', 'created_at' => now()->subMonths(2), 'updated_at' => now()->subMonth(), 'local' => ['municipio' => 'Betim', 'uf' => 'MG'], 'dados_gerais' => ['tipo_desastre' => 'Inundação', 'data_fato' => now()->subMonths(2)->toDateString()], 'created_by' => 1],
            ['id' => 'mock-009', 'protocolo' => 'RAT-2024-009', 'status' => 'finalizado', 'created_at' => now()->subMonths(3), 'updated_at' => now()->subMonths(2), 'local' => ['municipio' => 'Rib. das Neves', 'uf' => 'MG'], 'dados_gerais' => ['tipo_desastre' => 'Deslizamento', 'data_fato' => now()->subMonths(3)->toDateString()], 'created_by' => 2],
            ['id' => 'mock-010', 'protocolo' => 'RAT-2024-010', 'status' => 'finalizado', 'created_at' => now()->subMonth(), 'updated_at' => now()->subWeeks(2), 'local' => ['municipio' => 'Divinópolis', 'uf' => 'MG'], 'dados_gerais' => ['tipo_desastre' => 'Granizo', 'data_fato' => now()->subMonth()->toDateString()], 'created_by' => 4],
            ['id' => 'mock-011', 'protocolo' => 'RAT-2026-012', 'status' => 'rascunho', 'created_at' => now(), 'updated_at' => now(), 'local' => ['municipio' => 'Ouro Preto', 'uf' => 'MG'], 'dados_gerais' => ['tipo_desastre' => 'Deslizamento', 'data_fato' => now()->toDateString()], 'created_by' => 6],
            ['id' => 'mock-012', 'protocolo' => 'RAT-2026-013', 'status' => 'em_andamento', 'created_at' => now(), 'updated_at' => now(), 'local' => ['municipio' => 'Uberaba', 'uf' => 'MG'], 'dados_gerais' => ['tipo_desastre' => 'Vendaval', 'data_fato' => now()->toDateString()], 'created_by' => 1],
            ['id' => 'mock-013', 'protocolo' => 'RAT-2026-014', 'status' => 'em_andamento', 'created_at' => now()->subDay(), 'updated_at' => now(), 'local' => ['municipio' => 'Sete Lagoas', 'uf' => 'MG'], 'dados_gerais' => ['tipo_desastre' => 'Inundação', 'data_fato' => now()->subDay()->toDateString()], 'created_by' => 2],
            ['id' => 'mock-014', 'protocolo' => 'RAT-2026-015', 'status' => 'rascunho', 'created_at' => now()->subDays(2), 'updated_at' => now()->subDay(), 'local' => ['municipio' => 'Teófilo Otoni', 'uf' => 'MG'], 'dados_gerais' => ['tipo_desastre' => 'Enxurrada', 'data_fato' => now()->subDays(2)->toDateString()], 'created_by' => 3],
        ]);

        // Controller/UseCases esperam objetos (ex.: $rat->id). No mock, converter arrays em objetos.
        $rats = $rats->map(static fn (array $rat) => (object) $rat);

        // Aplicar filtro de busca no mock
        if (!empty($filters['search'])) {
            $search = strtolower($filters['search']);
            $rats = $rats->filter(function ($rat) use ($search) {
                return str_contains(strtolower($rat->protocolo), $search) ||
                       str_contains(strtolower($rat->status), $search) ||
                       (isset($rat->local['municipio']) && str_contains(strtolower($rat->local['municipio']), $search));
            });
        }

        // Simular paginação
        $total = $rats->count();
        $currentPage = request()->get('page', 1);

        if ($perPage === -1) {
            $perPage = $total > 0 ? $total : 1;
            $currentPage = 1;
        }

        $items = $rats->forPage($currentPage, $perPage);

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $currentPage,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }

    public function create(array $data): Rat
    {
        return Rat::create($data);
    }

    public function update(int $id, array $data): Rat
    {
        $rat = Rat::findOrFail($id);
        $rat->update($data);
        return $rat;
    }

    public function delete(int $id): bool
    {
        return (bool) Rat::destroy($id);
    }

    public function getStatistics(array $filters = []): array
    {
        // Tentar buscar do banco primeiro
        try {
            $count = Rat::count();
            if ($count > 0) {
                $query = Rat::query();

                if (isset($filters['ano']) && $filters['ano']) {
                    $query->whereYear('created_at', $filters['ano']);
                }

                $total = (clone $query)->count();
                $hoje = (clone $query)->whereDate('created_at', today())->count();
                $esteMes = (clone $query)->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)->count();
                $esteAno = (clone $query)->whereYear('created_at', now()->year)->count();

                return compact('total', 'hoje', 'esteMes', 'esteAno');
            }
        } catch (\Exception $e) {
            // Tabela não existe — usar mock
        }

        // Fallback: estatísticas mock
        return [
            'total'     => 15,
            'hoje'      => 3,
            'este_mes'  => 8,
            'este_ano'  => 15,
        ];
    }
}
