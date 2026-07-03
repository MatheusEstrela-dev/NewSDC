<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\Octane\OctaneDiagnostics;
use Illuminate\Console\Command;

class OctaneDiagnosticsCommand extends Command
{
    protected $signature = 'octane:diagnostics {--json : Emitir saida JSON para automacao}';

    protected $description = 'Mostra diagnostico de Octane/Swoole, hook_flags, pools e task workers';

    public function handle(OctaneDiagnostics $diagnostics): int
    {
        $snapshot = $diagnostics->snapshot();

        if ($this->option('json')) {
            $this->line((string) json_encode(
                $snapshot,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
            ));

            return self::SUCCESS;
        }

        $this->info('Octane diagnostics');
        $this->table(['Item', 'Valor'], $this->flatten($snapshot));

        return self::SUCCESS;
    }

    private function flatten(array $data, string $prefix = ''): array
    {
        $rows = [];

        foreach ($data as $key => $value) {
            $path = $prefix === '' ? (string) $key : "{$prefix}.{$key}";

            if (is_array($value)) {
                $rows = [...$rows, ...$this->flatten($value, $path)];
                continue;
            }

            $rows[] = [$path, $this->stringify($value)];
        }

        return $rows;
    }

    private function stringify(mixed $value): string
    {
        return match (true) {
            is_bool($value) => $value ? 'true' : 'false',
            $value === null => 'null',
            default => (string) $value,
        };
    }
}
