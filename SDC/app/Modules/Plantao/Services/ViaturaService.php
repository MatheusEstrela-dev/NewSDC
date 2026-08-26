<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Services;

use App\Modules\Plantao\Enums\StatusViatura;
use App\Modules\Plantao\Models\Viatura;
use App\Modules\Shared\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ViaturaService extends BaseService
{
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Viatura::query()
            ->with(['ultimoCondutor:id,name', 'movimentacaoAberta'])
            ->orderBy('prefixo')
            ->orderBy('placa');

        if (!empty($filters['status'])) {
            // Cards agregados (ex.: "Indisponiveis" = MANUTENCAO+CEDIDA+INDISPONIVEL)
            // mandam uma lista separada por virgula; sem virgula, comportamento
            // de igualdade simples permanece inalterado.
            $statusFiltro = str_contains($filters['status'], ',')
                ? array_values(array_filter(array_map('trim', explode(',', $filters['status']))))
                : $filters['status'];

            if (is_array($statusFiltro)) {
                $query->whereIn('status', $statusFiltro);
            } else {
                $query->where('status', $statusFiltro);
            }
        }

        if (!empty($filters['localizacao'])) {
            $query->where('localizacao', $filters['localizacao']);
        }

        if (array_key_exists('ativo', $filters) && $filters['ativo'] !== null && $filters['ativo'] !== '') {
            $query->where('ativo', filter_var($filters['ativo'], FILTER_VALIDATE_BOOLEAN));
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('placa', 'ilike', "%{$search}%")
                    ->orWhere('prefixo', 'ilike', "%{$search}%")
                    ->orWhere('modelo', 'ilike', "%{$search}%");
            });
        }

        if ($perPage === -1) {
            $total = (clone $query)->count();
            $perPage = $total > 0 ? $total : 1;
        }

        return $query->paginate($perPage);
    }

    public function find(int $id): ?Viatura
    {
        return Viatura::with(['ultimoCondutor:id,name', 'movimentacaoAberta'])->find($id);
    }

    public function create(array $data): Viatura
    {
        return Viatura::create($data);
    }

    public function update(int $id, array $data): Viatura
    {
        $viatura = Viatura::findOrFail($id);
        $viatura->update($data);

        return $viatura->fresh();
    }

    public function delete(int $id): bool
    {
        return (bool) Viatura::findOrFail($id)->delete();
    }

    /**
     * @return array{total:int,disponiveis:int,em_transito:int,indisponiveis:int}
     */
    public function getStatistics(): array
    {
        $porStatus = Viatura::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        $conta = static fn(StatusViatura ...$status): int => array_sum(
            array_map(fn(StatusViatura $s) => (int) ($porStatus[$s->value] ?? 0), $status)
        );

        return [
            'total' => array_sum($porStatus),
            'disponiveis' => $conta(StatusViatura::DISPONIVEL),
            'em_transito' => $conta(StatusViatura::EM_TRANSITO),
            'indisponiveis' => $conta(
                StatusViatura::MANUTENCAO,
                StatusViatura::CEDIDA,
                StatusViatura::INDISPONIVEL
            ),
        ];
    }
}
