<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Services;

use App\Modules\Demandas\Domain\Contracts\DemandaRepository;
use App\Modules\Shared\Support\CsvSeguro;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class DemandaCsvExporter
{
    public function __construct(private readonly DemandaRepository $repository) {}

    public function download(array $filters, int $viewerId, bool $manage): StreamedResponse
    {
        return response()->streamDownload(function () use ($filters, $viewerId, $manage): void {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Protocolo', 'Título', 'Tipo', 'Status', 'Abertura'], ';');
            $page = 1;
            do {
                $batch = $this->repository->paginate($filters, 500, $viewerId, $manage, $page);
                foreach ($batch->items() as $demanda) {
                    fputcsv($output, [
                        CsvSeguro::celula($demanda->protocolo),
                        CsvSeguro::celula($demanda->titulo),
                        $demanda->tipo->value,
                        $demanda->status->value,
                        $demanda->created_at?->format('d/m/Y H:i'),
                    ], ';');
                }
                $page++;
            } while ($batch->hasMorePages());
            fclose($output);
        }, 'demandas.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
