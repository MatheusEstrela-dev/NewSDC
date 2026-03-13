<?php

declare(strict_types=1);

namespace App\Modules\Rat\Services;

use App\Modules\Rat\DTOs\RatFilterDTO;
use App\Modules\Rat\Models\Rat;
use App\Modules\Rat\Services\RatStatisticsService;
use App\Modules\Shared\BaseService;
use Illuminate\Pagination\LengthAwarePaginator;

class RatService extends BaseService
{
    public function __construct(
        private readonly RatFilterService $filterService,
    ) {}

    public function list(RatFilterDTO $filters): LengthAwarePaginator
    {
        $query = Rat::with('creator:id,name')->orderBy('created_at', 'desc');
        $this->filterService->apply($query, $filters);

        return $query->paginate($filters->perPage);
    }

    public function findById(string $id): ?Rat
    {
        return Rat::with(['creator:id,name', 'orgaoEmissor', 'imagens'])->find($id);
    }

    public function delete(string $id): void
    {
        $rat = Rat::find($id);
        abort_if(is_null($rat), 404, 'RAT não encontrado');
        $rat->delete();
    }

    public function getStatistics(): array
    {
        return app(RatStatisticsService::class)->getStatistics()->toArray();
    }

    public function export(RatFilterDTO $filters): array
    {
        $query = Rat::query();
        $this->filterService->apply($query, $filters);

        return $query->get()->toArray();
    }
}
