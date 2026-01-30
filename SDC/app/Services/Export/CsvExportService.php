<?php

namespace App\Services\Export;

use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvExportService
{
    /**
     * Gera uma StreamedResponse para download de CSV.
     *
     * @param iterable $data Dados para exportar (array ou Collection)
     * @param array $headers Cabeçalhos das colunas (ex: ['ID', 'Nome', 'Data'])
     * @param callable $rowMapper Função para transformar cada item em um array indexado correspondente aos headers
     * @param string $filename Nome do arquivo (sem extensão .csv, ela será adicionada)
     * @return StreamedResponse
     */
    public function export(iterable $data, array $headers, callable $rowMapper, string $filename = 'export'): StreamedResponse
    {
        $csvFilename = $filename . '_' . date('Y-m-d_H-i-s') . '.csv';
        
        $responseHeaders = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $csvFilename . '"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($data, $headers, $rowMapper) {
            $handle = fopen('php://output', 'w');
            
            // Add BOM for Excel compatibility (UTF-8)
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // Escrever cabeçalhos
            fputcsv($handle, $headers, ';'); // Ponto e vírgula é melhor para Excel em PT-BR

            // Escrever linhas
            foreach ($data as $item) {
                fputcsv($handle, $rowMapper($item), ';');
            }

            fclose($handle);
        }, 200, $responseHeaders);
    }
}
