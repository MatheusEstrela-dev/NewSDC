<?php

declare(strict_types=1);

namespace App\Modules\Rat\Application\Services;

use App\Modules\Rat\Models\RatOcorrencia;
use App\Modules\Rat\Services\RatExportService;
use App\Modules\Rat\Services\RatStatisticsService;
use App\Modules\Rat\Services\RatWriteService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RatService
{
    public function __construct(
        private readonly RatStatisticsService $statisticsService,
        private readonly RatExportService     $exportService,
        private readonly RatWriteService      $writeService,
    ) {}

    public function createNew(): object
    {
        return $this->writeService->create();
    }

    public function createWithData(array $data): object
    {
        return $this->writeService->createWithData($data);
    }

    public function finalize(string $id): object
    {
        return $this->writeService->finalize($id);
    }

    public function getIndexData(): array
    {
        return [
            'rats'       => RatOcorrencia::with(['creator'])
                                ->orderByDesc('created_at')
                                ->paginate(15),
            'statistics' => $this->statisticsService->getStatistics()->toArray(),
        ];
    }

    public function findById(string $id): ?RatOcorrencia
    {
        return RatOcorrencia::with([
            'creator',
            'updater',
            'relatosMorph.conteudo',
            'historicos',
        ])->find($id);
    }

    public function delete(string $id): void
    {
        RatOcorrencia::where('id', $id)->delete();
    }

    public function export(): StreamedResponse
    {
        return $this->exportService->exportToCsv();
    }
}
