<?php

declare(strict_types=1);

namespace App\Modules\Rat\Services;

use App\Modules\Rat\DTO\RatFilterDTO;
use App\Modules\Rat\Models\Rat;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Responsabilidade única: exportar ocorrências RAT para CSV.
 */
class RatExportService
{
    public function exportToCsv(RatFilterDTO $filters): StreamedResponse
    {
        $query = Rat::orderBy('created_at', 'desc');

        if ($filters->numeroBos) {
            $query->where('numero_bos', 'like', '%' . $filters->numeroBos . '%');
        }
        if ($filters->status !== null) {
            $query->where('status', $filters->status);
        }
        if ($filters->dataInicio) {
            $query->whereDate('created_at', '>=', $filters->dataInicio);
        }
        if ($filters->dataFim) {
            $query->whereDate('created_at', '<=', $filters->dataFim);
        }

        $ocorrencias = $query->get(['id', 'numero_bos', 'status', 'historico', 'prazo_edicao', 'created_at']);
        $filename    = 'rat-ocorrencias-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($ocorrencias) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM
            fputcsv($handle, ['ID', 'Número BO', 'Status', 'Histórico', 'Prazo Edição', 'Criado em'], ';');
            foreach ($ocorrencias as $oc) {
                fputcsv($handle, [
                    $oc->id,
                    $oc->numero_bos ?? '',
                    $oc->status_label,
                    $oc->historico ?? '',
                    $oc->prazo_edicao?->format('d/m/Y H:i') ?? '',
                    $oc->created_at?->format('d/m/Y H:i') ?? '',
                ], ';');
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}