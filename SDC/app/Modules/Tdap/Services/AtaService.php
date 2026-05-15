<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Services;

use App\Modules\Tdap\DTOs\AtaDTO;
use App\Modules\Tdap\Models\Ata;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class AtaService
{
    /**
     * @param  array<string, mixed>  $filtros
     */
    public function listar(int $perPage = 15, array $filtros = []): LengthAwarePaginator
    {
        return Ata::query()
            ->withCount('lotes')
            ->when(
                array_key_exists('ativo', $filtros) && $filtros['ativo'] !== null && $filtros['ativo'] !== '',
                fn ($q) => $q->where('ativo', (bool) $filtros['ativo']),
            )
            ->when($filtros['vigente'] ?? null, fn ($q) => $q->vigente())
            ->when($filtros['search'] ?? null, fn ($q, $termo) => $q->buscar((string) $termo))
            ->orderByDesc('dt_inicio')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function obter(int $id): Ata
    {
        return Ata::query()
            ->withCount('lotes')
            ->with(['lotes' => fn ($q) => $q->with(['municipio:id,nome,uf', 'prestador:id,nome,cnpj'])])
            ->findOrFail($id);
    }

    public function criar(AtaDTO $dto): Ata
    {
        return DB::transaction(fn () => Ata::create($dto->toArray()));
    }

    public function atualizar(int $id, AtaDTO $dto): Ata
    {
        return DB::transaction(function () use ($id, $dto): Ata {
            $ata = Ata::findOrFail($id);
            $ata->update($dto->toArray());

            return $ata->fresh();
        });
    }

    public function deletar(int $id): bool
    {
        $ata = Ata::query()->withCount('lotes')->findOrFail($id);

        if ($ata->lotes_count > 0) {
            throw new \DomainException(
                "Ata {$ata->numero} possui {$ata->lotes_count} lote(s) vinculado(s). Remova-os antes."
            );
        }

        return (bool) $ata->delete();
    }

    /**
     * @return array<string, int>
     */
    public function obterEstatisticas(): array
    {
        $hoje = now()->toDateString();
        $row = Ata::query()
            ->selectRaw('
                COUNT(*) AS total,
                COUNT(*) FILTER (WHERE ativo = TRUE) AS ativos,
                COUNT(*) FILTER (WHERE ativo = TRUE AND dt_inicio <= ? AND dt_final >= ?) AS vigentes,
                COUNT(*) FILTER (WHERE ativo = TRUE AND dt_final < ?) AS encerradas
            ', [$hoje, $hoje, $hoje])
            ->first();

        return [
            'total'      => (int) ($row->total ?? 0),
            'ativos'     => (int) ($row->ativos ?? 0),
            'vigentes'   => (int) ($row->vigentes ?? 0),
            'encerradas' => (int) ($row->encerradas ?? 0),
        ];
    }
}
