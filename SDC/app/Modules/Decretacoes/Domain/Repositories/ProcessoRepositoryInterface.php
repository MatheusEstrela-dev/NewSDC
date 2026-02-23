<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes\Domain\Repositories;

use App\Modules\Decretacoes\Domain\Entities\Processo;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repository Interface: Processo
 */
interface ProcessoRepositoryInterface
{
    public function findById(int $id): ?Processo;
    public function create(array $data): Processo;
    public function update(int $id, array $data): Processo;
    public function delete(int $id): bool;
    public function all(): Collection;
    public function paginate(int $perPage = 15): LengthAwarePaginator;
    public function findAll(array $filters = [], int $perPage = 15): LengthAwarePaginator;
    public function filterBy(array $filters): Collection;
    public function count(): int;
}
