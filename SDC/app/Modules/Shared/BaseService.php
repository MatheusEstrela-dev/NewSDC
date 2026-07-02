<?php

declare(strict_types=1);

namespace App\Modules\Shared;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

abstract class BaseService
{
    /**
     * Pagina a query. $page explicito permite paginar fora do ciclo de request
     * (ex.: closures do Concurrency::tasks() em task workers, onde o resolver
     * padrao do Paginator nao enxerga a request); null preserva o comportamento
     * atual (resolve da request corrente).
     */
    protected function paginate(Builder $query, int $perPage = 15, ?int $page = null): LengthAwarePaginator
    {
        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    protected function applyFilters(Builder $query, array $filters, array $filterableFields): Builder
    {
        foreach ($filterableFields as $field) {
            if (!empty($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }

        return $query;
    }

    protected function applySearch(Builder $query, ?string $search, array $searchableFields): Builder
    {
        if (empty($search) || empty($searchableFields)) {
            return $query;
        }

        $query->where(function (Builder $q) use ($search, $searchableFields) {
            foreach ($searchableFields as $field) {
                $q->orWhere($field, 'like', "%{$search}%");
            }
        });

        return $query;
    }

    protected function applySorting(Builder $query, ?string $sortBy, string $sortOrder = 'asc', array $sortableFields = []): Builder
    {
        if (empty($sortBy)) {
            return $query;
        }

        if (!empty($sortableFields) && !in_array($sortBy, $sortableFields)) {
            return $query;
        }

        return $query->orderBy($sortBy, $sortOrder);
    }
}
