<?php

declare(strict_types=1);

namespace App\Modules\Shared\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

trait HasPagination
{
    protected function paginateQuery(Builder $query, int $perPage = 15): LengthAwarePaginator
    {
        return $query->paginate($perPage);
    }

    protected function getPerPage(array $filters, int $default = 15): int
    {
        $perPage = $filters['per_page'] ?? $default;

        return min(max((int) $perPage, 1), 100);
    }
}
