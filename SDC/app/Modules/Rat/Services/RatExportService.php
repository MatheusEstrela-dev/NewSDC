<?php

declare(strict_types=1);

namespace App\Modules\Rat\Services;

use App\Models\Rat\RatOcorrencia;
use App\Modules\Rat\Domain\Repositories\RatRepositoryInterface;
use App\Modules\Rat\DTOs\RatFilterDTO;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Responsabilidade unica: exportar RATs para CSV.
 * Usa tabela rat_ocorrencias (polimorficas).
 */
class RatExportService
{
    public function __construct(
        private readonly RatRepositoryInterface $repository
    ) {}

    /**
     * Gera download CSV com todos os RATs que atendem os filtros.
     */
    public function exportToCsv(RatFilterDTO $filters): StreamedResponse
    {
        $filtersAll = new RatFilterDTO(
            protocolo: $filters->protocolo,
            status: $filters->status,
            ano: $filters->ano,
            municipio: $filters->municipio,
            perPage: 9999
        );

        $rats = $this->repository->paginate($filtersAll)->items();

        $filename = 'rats-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(
            fn() => $this->writeCsv($rats),
            $filename,
            ['Content-Type' => 'text/csv; charset=UTF-8']
        );
    }

    private function writeCsv(iterable $rats): void
    {
        $handle = fopen('php://output', 'w');
        fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($handle, $this->buildHeaders(), ';');

        foreach ($rats as $rat) {
            fputcsv($handle, $this->buildRow($rat), ';');
        }

        fclose($handle);
    }

    private function buildHeaders(): array
    {
        return ['ID', 'Protocolo', 'Status', 'Municipio', 'Data Criacao'];
    }

    private function buildRow($rat): array
    {
        return [
            $rat->id,
            $rat->protocolo ?? '',
            $rat->status ?? '',
            $rat->local['municipio'] ?? '',
            $rat->created_at?->format('d/m/Y H:i') ?? '',
        ];
    }
}
