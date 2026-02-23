<?php

namespace App\Modules\Pae\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Export\CsvExportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaeProtocoloController extends Controller
{
    public function __construct(
        private readonly CsvExportService $csvExportService
    ) {
    }

    public function index(): \Inertia\Response
    {
        return \Inertia\Inertia::render('PaeProtocolosIndex');
    }

    public function export(Request $request): StreamedResponse
    {
        // Mock data since DB implementation is missing
        $protocolos = $this->getMockProtocolos();

        $headers = [
            'ID',
            'Número',
            'Situação',
            'Analista',
            'Empreendedor',
            'Data Criação'
        ];

        $mapper = function ($row) {
            return [
                $row['id'],
                $row['numero'],
                $row['situacao'],
                $row['analista'],
                $row['empreendedor'],
                $row['created_at']
            ];
        };

        return $this->csvExportService->export($protocolos, $headers, $mapper, 'pae_protocolos');
    }

    private function getMockProtocolos(): array
    {
        // Generating some mock data
        $data = [];
        for ($i = 1; $i <= 10; $i++) {
            $data[] = [
                'id' => $i,
                'numero' => 'PAE-2026-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'situacao' => $i % 2 == 0 ? 'Em Análise' : 'Concluído',
                'analista' => 'Analista ' . $i,
                'empreendedor' => 'Empresa ' . $i,
                'created_at' => now()->subDays($i)->format('Y-m-d H:i:s')
            ];
        }
        return $data;
    }
}
