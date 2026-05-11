<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Services;

use App\Modules\Tdap\DTOs\PrestadorDTO;
use App\Modules\Tdap\Models\Prestador;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PrestadorService
{
    /**
     * @param  array<string, mixed>  $filtros
     */
    public function listar(int $perPage = 15, array $filtros = []): LengthAwarePaginator
    {
        return Prestador::query()
            ->withCount('caminhoes')
            ->when(
                array_key_exists('ativo', $filtros) && $filtros['ativo'] !== null && $filtros['ativo'] !== '',
                fn ($q) => $q->where('ativo', (bool) $filtros['ativo']),
            )
            ->when($filtros['uf'] ?? null, fn ($q, $uf) => $q->where('uf', mb_strtoupper((string) $uf)))
            ->when($filtros['search'] ?? null, fn ($q, $termo) => $q->buscar((string) $termo))
            ->orderBy('nome')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function obter(int $id): Prestador
    {
        return Prestador::query()
            ->withCount('caminhoes')
            ->findOrFail($id);
    }

    public function criar(PrestadorDTO $dto): Prestador
    {
        return DB::transaction(fn () => Prestador::create($dto->toArray()));
    }

    public function atualizar(int $id, PrestadorDTO $dto): Prestador
    {
        return DB::transaction(function () use ($id, $dto): Prestador {
            $prestador = Prestador::findOrFail($id);
            $prestador->update($dto->toArray());

            return $prestador->fresh();
        });
    }

    public function deletar(int $id): bool
    {
        $prestador = Prestador::query()
            ->withCount('caminhoes')
            ->findOrFail($id);

        if ($prestador->caminhoes_count > 0) {
            throw new \DomainException(
                "Prestador {$prestador->nome} possui {$prestador->caminhoes_count} caminhao(oes) vinculado(s). Remova-os antes."
            );
        }

        return (bool) $prestador->delete();
    }

    /**
     * @return array<string, int>
     */
    public function obterEstatisticas(): array
    {
        $row = Prestador::query()
            ->selectRaw('
                COUNT(*) AS total,
                COUNT(*) FILTER (WHERE ativo = TRUE) AS ativos,
                COUNT(*) FILTER (WHERE ativo = FALSE) AS inativos
            ')
            ->first();

        return [
            'total'    => (int) ($row->total ?? 0),
            'ativos'   => (int) ($row->ativos ?? 0),
            'inativos' => (int) ($row->inativos ?? 0),
        ];
    }
}
