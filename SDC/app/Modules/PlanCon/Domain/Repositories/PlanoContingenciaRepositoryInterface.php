<?php

namespace App\Modules\PlanCon\Domain\Repositories;

use App\Modules\PlanCon\Domain\Entities\PlanoContingencia;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface PlanoContingenciaRepositoryInterface
{
    public function find(int $id): ?PlanoContingencia;

    public function findAll(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function getStatistics(): array;

    public function getMunicipiosComPlano(int $perPage = 15): LengthAwarePaginator;

    public function getMunicipiosSemPlano(int $perPage = 15): LengthAwarePaginator;

    public function create(array $data): PlanoContingencia;

    public function update(int $id, array $data): PlanoContingencia;

    public function delete(int $id): bool;
}
