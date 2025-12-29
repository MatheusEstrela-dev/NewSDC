<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Domain\Repositories;

use App\Modules\Treinamento\Domain\Entities\Treinamento;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface TreinamentoRepositoryInterface
{
    public function find(int $id): ?Treinamento;

    public function findAll(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function create(array $data): Treinamento;

    public function update(int $id, array $data): Treinamento;

    public function delete(int $id): bool;

    public function getStatistics(array $filters = []): array;
}
