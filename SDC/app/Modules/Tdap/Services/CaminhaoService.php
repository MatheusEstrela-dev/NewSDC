<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Services;

use App\Modules\Tdap\DTOs\CaminhaoDTO;
use App\Modules\Tdap\Models\Caminhao;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class CaminhaoService
{
    /**
     * @param  array<string, mixed>  $filtros
     */
    public function listar(int $perPage = 15, array $filtros = []): LengthAwarePaginator
    {
        return Caminhao::query()
            ->with(['prestador:id,nome,cnpj'])
            ->when(
                array_key_exists('ativo', $filtros) && $filtros['ativo'] !== null && $filtros['ativo'] !== '',
                fn ($q) => $q->where('ativo', (bool) $filtros['ativo']),
            )
            ->when(
                $filtros['prestador_id'] ?? null,
                fn ($q, $id) => $q->doPrestador((int) $id),
            )
            ->when($filtros['search'] ?? null, fn ($q, $termo) => $q->buscar((string) $termo))
            ->orderBy('placa')
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
        $rows = Caminhao::query()
            ->with(['prestador:id,nome,cnpj'])
            ->when(
                array_key_exists('ativo', $filtros) && $filtros['ativo'] !== null && $filtros['ativo'] !== '',
                fn ($q) => $q->where('ativo', (bool) $filtros['ativo']),
            )
            ->when(
                $filtros['prestador_id'] ?? null,
                fn ($q, $id) => $q->doPrestador((int) $id),
            )
            ->when($filtros['search'] ?? null, fn ($q, $termo) => $q->buscar((string) $termo))
            ->orderBy('placa')
            ->get();

        return $rows->map(fn (Caminhao $c) => [
            'Placa'          => $c->placa,
            'Marca'          => $c->marca,
            'Modelo'         => $c->modelo,
            'Cor'            => $c->cor,
            'Ano'            => $c->ano,
            'Capacidade m3'  => number_format((float) $c->capacidade_m3, 2, ',', '.'),
            'Prestador'      => $c->prestador?->nome,
            'CNPJ'           => $c->prestador?->cnpj,
            'Situacao'       => $c->ativo ? 'Ativo' : 'Inativo',
            'Observacoes'    => $c->observacoes,
        ])->all();
    }

    public function obter(int $id): Caminhao
    {
        return Caminhao::query()
            ->with(['prestador:id,nome,cnpj,email'])
            ->findOrFail($id);
    }

    public function criar(CaminhaoDTO $dto): Caminhao
    {
        return DB::transaction(fn () => Caminhao::create($dto->toArray()));
    }

    public function atualizar(int $id, CaminhaoDTO $dto): Caminhao
    {
        return DB::transaction(function () use ($id, $dto): Caminhao {
            $caminhao = Caminhao::findOrFail($id);
            $caminhao->update($dto->toArray());

            return $caminhao->fresh(['prestador']);
        });
    }

    public function deletar(int $id): bool
    {
        $caminhao = Caminhao::findOrFail($id);

        return (bool) $caminhao->delete();
    }

    /**
     * @return array<string, int|float>
     */
    public function obterEstatisticas(): array
    {
        $row = Caminhao::query()
            ->selectRaw('
                COUNT(*) AS total,
                COUNT(*) FILTER (WHERE ativo = TRUE) AS ativos,
                COUNT(*) FILTER (WHERE ativo = FALSE) AS inativos,
                COALESCE(SUM(capacidade_m3) FILTER (WHERE ativo = TRUE), 0) AS capacidade_total_m3
            ')
            ->first();

        return [
            'total'              => (int) ($row->total ?? 0),
            'ativos'             => (int) ($row->ativos ?? 0),
            'inativos'           => (int) ($row->inativos ?? 0),
            'capacidade_total_m3' => (float) ($row->capacidade_total_m3 ?? 0),
        ];
    }
}
