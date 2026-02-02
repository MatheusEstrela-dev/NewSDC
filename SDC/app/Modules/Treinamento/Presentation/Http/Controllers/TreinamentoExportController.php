<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Application\DTOs\TreinamentoListDTO;
use App\Modules\Treinamento\Application\UseCases\Treinamento\ListTreinamentosUseCase;
use App\Services\Export\CsvExportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TreinamentoExportController extends Controller
{
    public function __construct(
        private readonly ListTreinamentosUseCase $listUseCase,
        private readonly CsvExportService $csvExportService
    ) {
    }

    public function __invoke(Request $request): StreamedResponse
    {
        $filters = $request->only(['status', 'tipo', 'search']);

        try {
            // Paginate -1 to get all
            $treinamentosPaginator = $this->listUseCase->execute($filters, -1);
            $treinamentos = $treinamentosPaginator->items();
        } catch (\Exception $e) {
            $treinamentos = [];
        }

        $headers = [
            'Nome',
            'Tipo',
            'Status',
            'Vagas',
            'Inscritos',
            'Data Início',
            'Data Fim'
        ];

        $mapper = function ($t) {
            return [
                $t->nome ?? '',
                $t->tipo ?? '',
                $t->status ?? '',
                $t->vagas_totais ?? '',
                $t->vagas_preenchidas ?? '',
                $t->data_inicio ? $t->data_inicio->format('d/m/Y H:i') : '',
                $t->data_fim ? $t->data_fim->format('d/m/Y H:i') : '',
            ];
        };

        return $this->csvExportService->export($treinamentos, $headers, $mapper, 'treinamentos_export');
    }
}
