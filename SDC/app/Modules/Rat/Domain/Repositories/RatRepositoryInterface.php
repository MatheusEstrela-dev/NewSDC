<?php

declare(strict_types=1);

namespace App\Modules\Rat\Domain\Repositories;

use App\Modules\Rat\Models\RatOcorrencia;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface RatRepositoryInterface
{
    public function findById(string $id): ?RatOcorrencia;

    public function create(array $data): RatOcorrencia;

    public function paginate(int $perPage = 15): LengthAwarePaginator;

    public function getMunicipalities(): array;

    public function delete(string $id): void;

    public function updateStatus(string $id, int $status): void;

    public function update(string $id, array $data): RatOcorrencia;

    public function getLatestSequence(int $year): int;

    public function findAll(int $limit = 10): Collection;
}
