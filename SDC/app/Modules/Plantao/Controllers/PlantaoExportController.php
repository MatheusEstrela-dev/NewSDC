<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Plantao\Services\PlantaoService;
use App\Services\Export\CsvExportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PlantaoExportController extends Controller
{
    public function __construct(
        private readonly PlantaoService $plantaoService,
        private readonly CsvExportService $csvExportService
    ) {
    }

    public function __invoke(Request $request): StreamedResponse
    {
        $filters = $request->only(['status', 'periodo', 'search']);

        try {
            $plantoesPaginator = $this->plantaoService->list($filters, -1);
            $plantoes = $plantoesPaginator->items();
        } catch (\Exception $e) {
            $plantoes = [];
        }

        $headers = [
            'Data',
            'Plantonista',
            'Periodo',
            'Status',
            'Observacoes'
        ];

        $mapper = function ($plantao) {
            return [
                $plantao->data ? $plantao->data->format('d/m/Y') : '',
                $plantao->plantonista_nome ?? '',
                $plantao->periodo?->label() ?? $plantao->periodo ?? '',
                $plantao->status?->label() ?? $plantao->status ?? '',
                $plantao->observacoes ?? '',
            ];
        };

        return $this->csvExportService->export($plantoes, $headers, $mapper, 'plantoes_export');
    }
}
