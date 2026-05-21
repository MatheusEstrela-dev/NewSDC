<?php

declare(strict_types=1);

namespace App\Jobs\Decretacoes;

use App\Jobs\Concerns\TracksAsyncProgress;
use App\Models\RequestTrace;
use App\Modules\Decretacoes\Services\ProcessoQueryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\File;
use Illuminate\Http\Request;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Job assincrono para exportar dados normalizados para Power BI.
 * Substitui chamada sincrona de DecretacoesController::exportPowerBI quando
 * cliente usa a rota /api/v1/decretacoes/export-powerbi-async.
 */
class ExportPowerBIJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use TracksAsyncProgress;

    public int $tries = 2;
    public int $timeout = 600;

    /**
     * @param array<string, mixed> $filters filtros da request original
     */
    public function __construct(string $traceId, public array $filters)
    {
        $this->setTrace($traceId);
    }

    public function handle(ProcessoQueryService $queryService): void
    {
        $this->runTracked(function () use ($queryService): array {
            $request = Request::create('/', 'GET', $this->filters);
            $data = $queryService->exportAllForApiFlat($request);

            $disk = 'exports';
            $name = sprintf(
                'powerbi_%s_%s.csv',
                now()->format('Ymd_His'),
                substr($this->traceId ?? 'unknown', 0, 8),
            );

            // Escreve direto em arquivo temporario no disco (sem buffer
            // intermediario em php://temp + string). Padrao alinhado com
            // ExportRatToCsvJob; reduz memory peak para datasets grandes.
            $tmpPath = tempnam(sys_get_temp_dir(), 'powerbi-export-');
            if ($tmpPath === false) {
                throw new \RuntimeException('Falha ao criar arquivo temporario para export PowerBI');
            }

            try {
                $handle = fopen($tmpPath, 'w');
                if ($handle === false) {
                    throw new \RuntimeException("Falha ao abrir {$tmpPath} para escrita");
                }

                try {
                    fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

                    if (!empty($data)) {
                        fputcsv($handle, array_keys($data[0]), ';');
                    }
                    foreach ($data as $row) {
                        fputcsv($handle, array_values($row), ';');
                    }
                } finally {
                    fclose($handle);
                }

                Storage::disk($disk)->putFileAs('decretacoes', new File($tmpPath), $name);
            } finally {
                @unlink($tmpPath);
            }

            return ['disk' => $disk, 'path' => 'decretacoes/' . $name];
        });
    }

    /**
     * Marca trace como failed mesmo quando o job for cancelado externamente
     * (SIGKILL, max tries exceeded) — runTracked so cobre exceptions
     * capturadas dentro de handle().
     */
    public function failed(Throwable $e): void
    {
        if ($this->traceId) {
            RequestTrace::find($this->traceId)?->markAsFailed($e->getMessage());
        }
    }
}
