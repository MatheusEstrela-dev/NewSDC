<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Services;

use App\Modules\Cisterna\DTOs\CisternaDTO;
use App\Modules\Cisterna\Enums\StatusCisterna;
use App\Modules\Cisterna\Models\Cisterna;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class CisternaService
{
    /**
     * @param  array<string, mixed>  $filtros
     */
    public function listar(int $perPage = 15, array $filtros = []): LengthAwarePaginator
    {
        return Cisterna::query()
            ->with(['municipio:id,nome,uf'])
            ->when($filtros['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filtros['tipo'] ?? null, fn ($q, $tipo) => $q->where('tipo', $tipo))
            ->when($filtros['municipio_id'] ?? null, fn ($q, $id) => $q->doMunicipio((int) $id))
            ->when($filtros['search'] ?? null, fn ($q, $termo) => $q->buscarPorTermo($termo))
            ->orderBy('nome')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function obter(int $id): Cisterna
    {
        return Cisterna::query()
            ->with(['municipio:id,nome,uf'])
            ->findOrFail($id);
    }

    public function criar(CisternaDTO $dto): Cisterna
    {
        return DB::transaction(function () use ($dto): Cisterna {
            return Cisterna::create($dto->toArray());
        });
    }

    public function atualizar(int $id, CisternaDTO $dto): Cisterna
    {
        return DB::transaction(function () use ($id, $dto): Cisterna {
            $cisterna = Cisterna::findOrFail($id);
            $cisterna->update($dto->toArray());

            return $cisterna->fresh(['municipio']);
        });
    }

    public function deletar(int $id): bool
    {
        $cisterna = Cisterna::findOrFail($id);

        return (bool) $cisterna->delete();
    }

    /**
     * @return array<string, int>
     */
    public function obterEstatisticas(): array
    {
        $row = Cisterna::query()
            ->selectRaw('
                COUNT(*) AS total,
                COUNT(*) FILTER (WHERE status = ?) AS ativas,
                COUNT(*) FILTER (WHERE status = ?) AS pendentes,
                COUNT(DISTINCT municipio_id) AS municipios
            ', [
                StatusCisterna::ATIVA->value,
                StatusCisterna::PENDENTE->value,
            ])
            ->first();

        return [
            'total' => (int) ($row->total ?? 0),
            'ativas' => (int) ($row->ativas ?? 0),
            'pendentes' => (int) ($row->pendentes ?? 0),
            'municipios' => (int) ($row->municipios ?? 0),
        ];
    }
}
