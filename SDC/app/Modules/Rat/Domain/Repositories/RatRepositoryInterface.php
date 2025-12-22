<?php

namespace App\Modules\Rat\Domain\Repositories;

use App\Modules\Rat\Domain\Entities\Rat;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface RatRepositoryInterface
{
    public function find(int $id): ?Rat;
    
    public function findAll(array $filters = [], int $perPage = 15): LengthAwarePaginator;
    
    public function create(array $data): Rat;
    
    public function update(int $id, array $data): Rat;
    
    public function delete(int $id): bool;
    
    public function getStatistics(array $filters = []): array;
}

