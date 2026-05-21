<?php

declare(strict_types=1);

namespace App\Jobs\Rat;

use App\Jobs\Concerns\TracksAsyncProgress;
use App\Models\RequestTrace;
use App\Modules\Rat\Services\RatExportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\File;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Job assincrono para exportar RAT em CSV. Substitui chamada sincrona
 * de RatUnifiedController::export quando cliente usa a rota /export/async.
 * Streaming via arquivo temporario para minimizar uso de memoria.
 */
class ExportRatToCsvJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use TracksAsyncProgress;

    public int $tries = 2;
    public int $timeout = 600;

    /**
     * @param array<string, mixed> $filters filtros (type, data_inicio, data_fim)
     */
    public function __construct(string $traceId, public array $filters)
    {
        $this->setTrace($traceId);
    }

    public function handle(RatExportService $exportService): void
    {
        $this->runTracked(function () use ($exportService): array {
            $disk = 'exports';
            $name = sprintf(
                'rat_%s_%s.csv',
                now()->format('Ymd_His'),
                substr($this->traceId ?? 'x', 0, 8),
            );

            $tmpPath = tempnam(sys_get_temp_dir(), 'rat-export-');
            if ($tmpPath === false) {
                throw new \RuntimeException('Falha ao criar arquivo temporario para export RAT');
            }

            try {
                $handle = fopen($tmpPath, 'w');
                if ($handle === false) {
                    throw new \RuntimeException("Falha ao abrir {$tmpPath} para escrita");
                }

                $exportService->writeCsvTo($handle, $this->filters);
                fclose($handle);

                Storage::disk($disk)->putFileAs('rat', new File($tmpPath), $name);
            } finally {
                @unlink($tmpPath);
            }

            return ['disk' => $disk, 'path' => 'rat/' . $name];
        });
    }

    /**
     * Garante que o trace transite para failed mesmo quando o job for
     * cancelado externamente (SIGKILL, max tries exceeded) — runTracked
     * so cobre exceptions capturadas dentro de handle().
     */
    public function failed(Throwable $e): void
    {
        if ($this->traceId) {
            RequestTrace::find($this->traceId)?->markAsFailed($e->getMessage());
        }
    }
}
