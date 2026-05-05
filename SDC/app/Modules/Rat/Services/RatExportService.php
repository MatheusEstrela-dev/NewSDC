<?php

declare(strict_types=1);

namespace App\Modules\Rat\Services;

use App\Modules\Rat\Models\RatOcorrencia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RatExportService
{
    public function exportToCsv(): StreamedResponse
    {
        $filename = 'rat-' . now()->format('Y-m-d_H-i-s') . '.csv';

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, ['ID', 'Protocolo', 'Status', 'Criado em'], ';');

            RatOcorrencia::orderByDesc('created_at')->each(function ($oc) use ($handle) {
                fputcsv($handle, [
                    $oc->id,
                    $oc->numero_bos,
                    $oc->status === 1 ? 'Finalizado' : 'Rascunho',
                    $oc->created_at?->format('d/m/Y H:i'),
                ], ';');
            });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
