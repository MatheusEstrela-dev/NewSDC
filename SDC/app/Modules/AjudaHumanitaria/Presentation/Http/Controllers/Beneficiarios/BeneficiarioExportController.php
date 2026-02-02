<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Presentation\Http\Controllers\Beneficiarios;

use App\Http\Controllers\Controller;
use App\Modules\AjudaHumanitaria\Domain\Repositories\BeneficiarioRepositoryInterface;
use App\Services\Export\CsvExportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BeneficiarioExportController extends Controller
{
    public function __construct(
        private readonly BeneficiarioRepositoryInterface $beneficiarioRepository,
        private readonly CsvExportService $csvExportService
    ) {
    }

    public function __invoke(Request $request): StreamedResponse
    {
        $filters = $request->only([
            'search',
            'status',
            'situacao_vulnerabilidade',
            'tipo_cadastro',
            'municipio_id',
            'abrigo_id',
        ]);

        try {
            // Paginate -1 to get all
            $beneficiariosPaginator = $this->beneficiarioRepository->list($filters, -1);
            $beneficiarios = $beneficiariosPaginator->items();
        } catch (\Exception $e) {
            // Fallback for mock if needed, or empty array
            $beneficiarios = [];
            // In a real scenario, we might want to use MockDataHelper here too if DB fails
        }

        $headers = [
            'Nome Responsável',
            'CPF',
            'Status',
            'Situação',
            'Tipo Cadastro',
            'Data Cadastro',
        ];

        $mapper = function ($b) {
            // $b is likely an Entity or Model. Assuming Entity via Repository usually returns Model/Entity.
            // Eloquent Repository returns Eloquent Models.
            return [
                $b->nome_responsavel ?? '',
                $b->cpf ?? '',
                $b->status ?? '',
                $b->situacao_vulnerabilidade ?? '',
                $b->tipo_cadastro ?? '',
                $b->created_at ? $b->created_at->format('d/m/Y H:i') : '',
            ];
        };

        return $this->csvExportService->export($beneficiarios, $headers, $mapper, 'beneficiarios_export');
    }
}
