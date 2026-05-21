<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\RequestTrace;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Remove artefatos antigos do disk 'exports' (gerados por jobs assincronos
 * que usam AsynchronousResponse). Tambem marca os traces associados como
 * expirados para o cliente nao tentar baixar arquivo inexistente.
 *
 * Uso: php artisan exports:cleanup --days=7
 */
class CleanupOldExportsCommand extends Command
{
    protected $signature = 'exports:cleanup
                            {--days=7 : idade minima em dias para remocao}
                            {--dry-run : nao remove, apenas lista}';

    protected $description = 'Remove artefatos antigos do disk exports (RequestTrace + arquivos)';

    public function handle(): int
    {
        $cutoff = now()->subDays((int) $this->option('days'));
        $dryRun = (bool) $this->option('dry-run');
        $removed = 0;
        $errors = 0;

        $this->info(sprintf(
            'Limpando exports criados antes de %s (%s)',
            $cutoff->toDateTimeString(),
            $dryRun ? 'DRY RUN' : 'EXECUTANDO'
        ));

        RequestTrace::query()
            ->where('status', RequestTrace::STATUS_COMPLETED)
            ->whereNotNull('result_disk')
            ->whereNotNull('result_path')
            ->where('completed_at', '<', $cutoff)
            ->orderBy('completed_at')
            ->chunkById(200, function ($batch) use ($dryRun, &$removed, &$errors): void {
                foreach ($batch as $trace) {
                    try {
                        $disk = Storage::disk($trace->result_disk);
                        $exists = $disk->exists($trace->result_path);

                        if ($dryRun) {
                            $this->line(sprintf(
                                '  [dry] trace=%s file=%s exists=%s',
                                substr($trace->id, 0, 8),
                                $trace->result_path,
                                $exists ? 'yes' : 'no'
                            ));
                            $removed++;
                            continue;
                        }

                        if ($exists) {
                            $disk->delete($trace->result_path);
                        }

                        $trace->update([
                            'result_disk' => null,
                            'result_path' => null,
                        ]);

                        $removed++;
                    } catch (\Throwable $e) {
                        $errors++;
                        $this->error("trace={$trace->id}: {$e->getMessage()}");
                    }
                }
            });

        $this->info("Removidos: {$removed}; Erros: {$errors}");

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }
}
