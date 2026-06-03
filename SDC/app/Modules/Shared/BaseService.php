<?php

declare(strict_types=1);

namespace App\Modules\Shared;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Throwable;

abstract class BaseService
{
    use LogsActivity;
    protected function paginate(Builder $query, int $perPage = 15): LengthAwarePaginator
    {
        return $query->paginate($perPage);
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

    protected function execute(string $operation, callable $callback, bool $useTransaction = false): mixed
    {
        return $this->wrapWithLogging($operation, function () use ($callback, $useTransaction) {
            if ($useTransaction) {
                return DB::transaction($callback);
            }
            return $callback();
        });
    }

    protected function executeInTransaction(string $operation, callable $callback): mixed
    {
        return $this->execute($operation, $callback, true);
    }

    protected function safeExecute(string $operation, callable $callback, mixed $default = null): mixed
    {
        try {
            return $this->execute($operation, $callback);
        } catch (Throwable $e) {
            $this->logException($e, ['operation' => $operation, 'fallback' => true]);
            return $default;
        }
    }
}
