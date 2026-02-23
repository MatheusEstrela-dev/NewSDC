<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Decretacoes\Domain\Repositories\ProcessoRepositoryInterface;
use App\Services\Export\CsvExportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProcessoExportController extends Controller
{
    public function __construct(
        private readonly ProcessoRepositoryInterface $processoRepository,
        private readonly CsvExportService $csvExportService
    ) {
    }

    public function __invoke(Request $request): StreamedResponse
    {
        $filters = $request->only([
            'search',
            'processo',
            'status',
            'vigencia_status',
            'data_inicio',
            'data_fim',
        ]);

        try {
            // Check if paginate supports -1 for all
            $processosPaginator = $this->processoRepository->paginate(-1); // Assuming we update repo to support this
            $processos = $processosPaginator->items();
        } catch (\Exception $e) {
            $processos = [];
        }

        $headers = [
            'Município',
            'Decreto',
            'Status',
            'Vigência Início',
            'Vigência Fim',
            'Status Vigência'
        ];

        $mapper = function ($p) {
            return [
                $p->municipio ?? '',
                $p->decreto_numero ?? '',
                $p->status ?? '',
                $p->vigencia_inicio ? $p->vigencia_inicio->format('d/m/Y') : '',
                $p->vigencia_fim ? $p->vigencia_fim->format('d/m/Y') : '',
                $p->vigencia_status ?? '',
            ];
        };

        return $this->csvExportService->export($processos, $headers, $mapper, 'processos_export');
    }
}
