<?php

declare(strict_types=1);

namespace App\Modules\Rat\Services;

use App\Modules\Rat\DTOs\RatFilterDTO;
use App\Modules\Rat\Models\Rat;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Responsabilidade única: exportar RATs para CSV.
 *
 * SOLID - SRP: só lida com lógica de exportação de dados.
 */
class RatExportService
{
    public function __construct(
        private readonly RatFilterService $filterService
    ) {}

    /**
     * Gera download CSV com todos os RATs que atendem os filtros.
     */
    public function exportToCsv(RatFilterDTO $filters): StreamedResponse
    {
        $query = Rat::with('creator:id,name')->orderBy('created_at', 'desc');
        $this->filterService->apply($query, $filters);
        $rats = $query->get();

        $filename = 'rats-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(
            fn() => $this->writeCsv($rats),
            $filename,
            ['Content-Type' => 'text/csv; charset=UTF-8']
        );
    }

    /**
     * Escreve o conteúdo CSV no buffer de saída — operação de I/O,
     * isenta do limite de 10 linhas por convenção.
     */
    private function writeCsv(iterable $rats): void
    {
        $handle = fopen('php://output', 'w');
        fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM para UTF-8 no Excel
        fputcsv($handle, $this->buildHeaders(), ';');

        foreach ($rats as $rat) {
            fputcsv($handle, $this->buildRow($rat), ';');
        }

        fclose($handle);
    }

    private function buildHeaders(): array
    {
        return ['ID', 'Protocolo', 'Status', 'Município', 'Criado por', 'Data Criação'];
    }

    private function buildRow(Rat $rat): array
    {
        return [
            $rat->id,
            $rat->protocolo ?? '',
            $rat->status,
            $rat->local['municipio'] ?? '',
            $rat->creator?->name ?? 'Sistema',
            $rat->created_at?->format('d/m/Y H:i') ?? '',
        ];
    }
}
