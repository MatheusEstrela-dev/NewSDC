<?php

declare(strict_types=1);

namespace App\Modules\Pmda\Services;

use App\Modules\Pmda\Enums\PmdaStatus;
use App\Modules\Pmda\Models\PmdaPlano;
use App\Modules\Shared\BaseService;
use Illuminate\Pagination\LengthAwarePaginator;

class PmdaPlanoService extends BaseService
{
    public function criar(int $municipioId, int $userId, array $data): PmdaPlano
    {
        $existeRascunho = PmdaPlano::query()
            ->where('municipio_id', $municipioId)
            ->where('status', PmdaStatus::RASCUNHO->value)
            ->exists();

        if ($existeRascunho) {
            throw new \DomainException('Já existe um PMDA em edição para este município.');
        }

        return PmdaPlano::create(array_merge($data, [
            'municipio_id' => $municipioId,
            'status'       => PmdaStatus::RASCUNHO,
            'created_by'   => $userId,
        ]));
    }

    public function atualizar(PmdaPlano $plano, array $data, int $userId): PmdaPlano
    {
        $plano->update(array_merge($data, [
            'updated_by'          => $userId,
            'dt_ultima_alteracao' => now(),
        ]));

        return $plano->refresh();
    }

    public function listar(array $filtros = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = PmdaPlano::query()->with('municipio')->latest('data');
        $query = $this->applyFilters($query, $filtros, ['municipio_id', 'status']);

        return $this->paginate($query, $perPage);
    }
}
