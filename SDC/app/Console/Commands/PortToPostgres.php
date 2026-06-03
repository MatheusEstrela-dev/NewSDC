<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Formatter\OutputFormatter;

class PortToPostgres extends Command
{
    protected $signature = 'db:port-postgres
        {--dry-run : Mostra mudancas sem escrever arquivos}
        {--batch= : Executa apenas uma categoria: json, timestamps}';

    protected $description = 'Porta migrations de MySQL para PostgreSQL (json->jsonb+GIN, useCurrentOnUpdate)';

    private bool $dryRun;
    private int $filesChanged = 0;
    private int $replacements = 0;

    public function handle(): int
    {
        $this->dryRun = (bool) $this->option('dry-run');
        $batch = $this->option('batch');

        if ($this->dryRun) {
            $this->warn('[DRY RUN] Nenhum arquivo sera escrito.');
            $this->newLine();
        }

        $migrations = glob(database_path('migrations/*.php'));

        foreach ($migrations as $path) {
            $original = file_get_contents($path);
            $content = $original;

            if (!$batch || $batch === 'json') {
                $content = $this->convertJson($content, $path);
            }

            if (!$batch || $batch === 'timestamps') {
                $content = $this->removeUseCurrentOnUpdate($content);
            }

            if ($content !== $original) {
                $this->filesChanged++;

                if ($this->dryRun) {
                    $this->showChangedLines($path, $original, $content);
                } else {
                    file_put_contents($path, $content);
                    $this->info('Atualizado: ' . basename($path));
                }
            }
        }

        $this->newLine();
        $this->table(
            ['Metrica', 'Valor'],
            [
                ['Arquivos alterados', $this->filesChanged],
                ['Substituicoes realizadas', $this->replacements],
            ]
        );

        if (!$this->dryRun) {
            $this->newLine();
            $this->verifyResults($batch);
        }

        return self::SUCCESS;
    }

    private function convertJson(string $content, string $path): string
    {
        if (!str_contains($content, '->json(')) {
            return $content;
        }

        // Rastreia o nome da tabela atual linha por linha.
        // Suporta multiplos Schema::create/table() no mesmo arquivo.
        // Para column definitions multi-linha (encadeamento), injeta o GIN index
        // somente apos a linha que fecha a statement com ';'.
        $lines = explode("\n", $content);
        $result = [];
        $currentTable = pathinfo($path, PATHINFO_FILENAME);
        $pendingGin = null; // ['indent' => string, 'colName' => string, 'table' => string]

        foreach ($lines as $line) {
            if (preg_match("/Schema::(?:create|table)\('([^']+)'/", $line, $m)) {
                $currentTable = $m[1];
            }

            // Se temos injecao pendente e esta linha fecha a statement (termina em ;)
            if ($pendingGin !== null && str_ends_with(rtrim($line), ';')) {
                $result[] = $line;
                $idxName = 'idx_' . $pendingGin['table'] . '_' . $pendingGin['colName'];
                $result[] = $pendingGin['indent'] . '$table->index(\'' . $pendingGin['colName'] . '\', \'' . $idxName . '\', \'gin\');';
                $this->replacements++;
                $pendingGin = null;
                continue;
            }

            if (preg_match('/^(\s*)\$table->json\(\'([^\']+)\'\)/', $line, $m)) {
                $indent = $m[1];
                $colName = $m[2];
                $line = str_replace('->json(', '->jsonb(', $line);

                if (str_ends_with(rtrim($line), ';')) {
                    // Statement single-line: injeta GIN imediatamente
                    $result[] = $line;
                    $idxName = 'idx_' . $currentTable . '_' . $colName;
                    $result[] = $indent . '$table->index(\'' . $colName . '\', \'' . $idxName . '\', \'gin\');';
                    $this->replacements++;
                } else {
                    // Statement multi-linha: aguarda o ; para injetar
                    $result[] = $line;
                    $pendingGin = ['indent' => $indent, 'colName' => $colName, 'table' => $currentTable];
                }
                continue;
            }

            $result[] = $line;
        }

        return implode("\n", $result);
    }

    private function removeUseCurrentOnUpdate(string $content): string
    {
        if (!str_contains($content, '->useCurrentOnUpdate()')) {
            return $content;
        }

        $count = substr_count($content, '->useCurrentOnUpdate()');
        $this->replacements += $count;
        return str_replace('->useCurrentOnUpdate()', '', $content);
    }

    private function verifyResults(?string $batch): void
    {
        $this->info('=== Verificacao ===');
        $dir = database_path('migrations');

        if (!$batch || $batch === 'json') {
            $n = $this->grepCount('->json(', $dir);
            $label = $n === 0
                ? '<fg=green>OK (0 restantes)</>'
                : "<fg=red>FAIL ({$n} restantes)</>";
            $this->line("->json() restantes: {$label}");
        }

        if (!$batch || $batch === 'timestamps') {
            $n = $this->grepCount('useCurrentOnUpdate', $dir);
            $label = $n === 0
                ? '<fg=green>OK (0 restantes)</>'
                : "<fg=red>FAIL ({$n} restantes)</>";
            $this->line("useCurrentOnUpdate restantes: {$label}");
        }
    }

    private function grepCount(string $needle, string $dir): int
    {
        $count = 0;
        foreach (glob($dir . '/*.php') as $file) {
            $count += substr_count(file_get_contents($file), $needle);
        }
        return $count;
    }

    private function showChangedLines(string $path, string $original, string $new): void
    {
        $this->warn('--- ' . basename($path) . ' ---');
        $origLines = explode("\n", $original);
        $newLines  = explode("\n", $new);

        $max = max(\count($origLines), \count($newLines));
        for ($i = 0; $i < $max; $i++) {
            $o = $origLines[$i] ?? null;
            $n = $newLines[$i] ?? null;
            if ($o !== $n) {
                if ($o !== null) {
                    $this->line('<fg=red>- ' . OutputFormatter::escape($o) . '</>');
                }
                if ($n !== null) {
                    $this->line('<fg=green>+ ' . OutputFormatter::escape($n) . '</>');
                }
            }
        }
        $this->newLine();
    }
}
