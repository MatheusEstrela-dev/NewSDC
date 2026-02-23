<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Domain\Repositories;

use App\Modules\Plantao\Domain\Entities\Plantao;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PlantaoRepositoryInterface
{
    public function find(int $id): ?Plantao;

    public function findAll(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function create(array $data): Plantao;

    public function update(int $id, array $data): Plantao;

    public function delete(int $id): bool;

    public function getStatistics(array $filters = []): array;
}
