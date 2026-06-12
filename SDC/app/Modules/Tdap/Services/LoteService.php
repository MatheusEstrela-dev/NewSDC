<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Services;

use App\Modules\Tdap\DTOs\LoteDTO;
use App\Modules\Tdap\Models\Lote;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class LoteService
{
    /**
     * @param  array<string, mixed>  $filtros
     */
    public function listar(int $perPage = 15, array $filtros = []): LengthAwarePaginator
    {
        return Lote::query()
            ->with([
                'ata:id,numero,dt_inicio,dt_final',
                'municipio:id,nome,uf',
                'prestador:id,nome,cnpj',
            ])
            ->when(
                array_key_exists('ativo', $filtros) && $filtros['ativo'] !== null && $filtros['ativo'] !== '',
                fn ($q) => $q->where('ativo', (bool) $filtros['ativo']),
            )
            ->when($filtros['ata_id'] ?? null, fn ($q, $id) => $q->daAta((int) $id))
            ->when($filtros['municipio_id'] ?? null, fn ($q, $id) => $q->doMunicipio((int) $id))
            ->when($filtros['prestador_id'] ?? null, fn ($q, $id) => $q->doPrestador((int) $id))
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Linhas planas para exportacao CSV (respeita os filtros da listagem).
     *
     * @param  array<string, mixed>  $filtros
     * @return array<int, array<string, mixed>>
     */
    public function exportar(array $filtros = []): array
    {
        $rows = Lote::query()
            ->with([
                'ata:id,numero',
                'municipio:id,nome,uf',
                'prestador:id,nome,cnpj',
            ])
            ->when(
                array_key_exists('ativo', $filtros) && $filtros['ativo'] !== null && $filtros['ativo'] !== '',
                fn ($q) => $q->where('ativo', (bool) $filtros['ativo']),
            )
            ->when($filtros['ata_id'] ?? null, fn ($q, $id) => $q->daAta((int) $id))
            ->when($filtros['municipio_id'] ?? null, fn ($q, $id) => $q->doMunicipio((int) $id))
            ->when($filtros['prestador_id'] ?? null, fn ($q, $id) => $q->doPrestador((int) $id))
            ->orderByDesc('id')
            ->get();

        return $rows->map(fn (Lote $l) => [
            'Numero'         => $l->numero,
            'Nome'           => $l->nome,
            'Ata'            => $l->ata?->numero,
            'Municipio'      => $l->municipio?->nome,
            'UF'             => $l->municipio?->uf,
            'Prestador'      => $l->prestador?->nome,
            'CNPJ'           => $l->prestador?->cnpj,
            'Volume (m3)'    => number_format((float) $l->qtd_agua_m3, 2, ',', '.'),
            'Valor m3 (R$)'  => number_format((float) $l->valor_m3, 2, ',', '.'),
            'Valor Total (R$)' => number_format($l->valor_total, 2, ',', '.'),
            'Situacao'       => $l->ativo ? 'Ativo' : 'Inativo',
        ])->all();
    }

    public function obter(int $id): Lote
    {
        return Lote::query()
            ->with([
                'ata:id,numero,dt_inicio,dt_final',
                'municipio:id,nome,uf',
                'prestador:id,nome,cnpj,email',
            ])
            ->findOrFail($id);
    }

    public function criar(LoteDTO $dto): Lote
    {
        return DB::transaction(fn () => Lote::create($dto->toArray()));
    }

    public function atualizar(int $id, LoteDTO $dto): Lote
    {
        return DB::transaction(function () use ($id, $dto): Lote {
            $lote = Lote::findOrFail($id);
            $lote->update($dto->toArray());

            return $lote->fresh(['ata', 'municipio', 'prestador']);
        });
    }

    public function deletar(int $id): bool
    {
        $lote = Lote::findOrFail($id);

        return (bool) $lote->delete();
    }

    /**
     * @return array<string, int|float>
     */
    public function obterEstatisticas(): array
    {
        $row = Lote::query()
            ->selectRaw('
                COUNT(*) AS total,
                COUNT(*) FILTER (WHERE ativo = TRUE) AS ativos,
                COALESCE(SUM(qtd_agua_m3) FILTER (WHERE ativo = TRUE), 0) AS volume_total_m3,
                COALESCE(SUM(qtd_agua_m3 * valor_m3) FILTER (WHERE ativo = TRUE), 0) AS valor_total
            ')
            ->first();

        return [
            'total'           => (int) ($row->total ?? 0),
            'ativos'          => (int) ($row->ativos ?? 0),
            'volume_total_m3' => (float) ($row->volume_total_m3 ?? 0),
            'valor_total'     => (float) ($row->valor_total ?? 0),
        ];
    }
}
