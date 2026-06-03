<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\RequestTrace;
use Illuminate\Console\Command;

/**
 * Mostra estatisticas de traces (RequestTrace) por status e por tipo.
 * Util para troubleshoot operacional e dashboards informais via CLI.
 *
 * Uso:
 *   php artisan traces:status
 *   php artisan traces:status --type=export_rat_csv
 *   php artisan traces:status --since=24h
 */
class TracesStatusCommand extends Command
{
    protected $signature = 'traces:status
                            {--type= : filtrar por tipo (ex: export_rat_csv)}
                            {--since=24h : janela de tempo (ex: 1h, 24h, 7d)}';

    protected $description = 'Mostra estatisticas de RequestTrace por status e tipo';

    public function handle(): int
    {
        $cutoff = $this->parseSince((string) $this->option('since'));
        $type = $this->option('type');

        $this->info(sprintf(
            'Traces desde %s%s',
            $cutoff->toDateTimeString(),
            $type ? " | type={$type}" : ''
        ));

        $query = RequestTrace::query()->where('created_at', '>=', $cutoff);
        if ($type) {
            $query->where('type', $type);
        }

        $totals = $query->clone()
            ->selectRaw('status, COUNT(*) AS total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        $this->table(
            ['Status', 'Total'],
            collect([
                RequestTrace::STATUS_PENDING,
                RequestTrace::STATUS_PROCESSING,
                RequestTrace::STATUS_COMPLETED,
                RequestTrace::STATUS_FAILED,
            ])->map(fn (string $s) => [$s, $totals[$s] ?? 0])->all()
        );

        if (!$type) {
            $byType = $query->clone()
                ->selectRaw('type, status, COUNT(*) AS total')
                ->groupBy('type', 'status')
                ->orderBy('type')
                ->get();

            if ($byType->isNotEmpty()) {
                $this->newLine();
                $this->info('Por tipo:');
                $this->table(
                    ['Type', 'Status', 'Total'],
                    $byType->map(fn ($r) => [$r->type, $r->status, $r->total])->all()
                );
            }
        }

        return self::SUCCESS;
    }

    private function parseSince(string $input): \Illuminate\Support\Carbon
    {
        $input = strtolower(trim($input));
        if (preg_match('/^(\d+)\s*([hd])$/', $input, $m)) {
            $value = (int) $m[1];
            return $m[2] === 'h' ? now()->subHours($value) : now()->subDays($value);
        }

        return now()->subHours(24);
    }
}
