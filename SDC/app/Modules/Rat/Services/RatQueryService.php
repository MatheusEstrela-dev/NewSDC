<?php

declare(strict_types=1);

namespace App\Modules\Rat\Services;

use App\Modules\Rat\DTO\RatFilterDTO;
use App\Modules\Rat\Models\Rat;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Consultas e filtros sobre rat_ocorrencias.
 *
 * FLUXO: Controller -> RatQueryService -> Rat (Model) -> rat_ocorrencias
 *
 * Métodos públicos:
 *   list()       lista paginada com filtros
 *   findById()   busca por ID ou retorna null
 */
class RatQueryService
{
    /**
     * Lista paginada com filtros opcionais.
     *
     * @param RatFilterDTO $filters Critérios de busca
     * @return LengthAwarePaginator Lista paginada
     */
    public function list(RatFilterDTO $filters): LengthAwarePaginator
    {
        $query = Rat::orderBy('created_at', 'desc');

        if ($filters->numeroBos) {
            $query->where('numero_bos', 'like', '%' . $filters->numeroBos . '%');
        }
        if ($filters->status !== null) {
            $query->where('status', $filters->status);
        }
        if ($filters->dataInicio) {
            $query->whereDate('created_at', '>=', $filters->dataInicio);
        }
        if ($filters->dataFim) {
            $query->whereDate('created_at', '<=', $filters->dataFim);
        }

        return $query->paginate($filters->perPage);
    }

    /**
     * Busca ocorrência por ID, retorna null se não encontrada.
     *
     * @param int $id ID da ocorrência
     * @return Rat|null
     */
    public function findById(int $id): ?Rat
    {
        return Rat::find($id);
    }
}
