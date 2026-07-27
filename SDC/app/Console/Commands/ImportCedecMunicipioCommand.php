<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\Legacy\MysqlDumpReader;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Popula a tabela `cedec_municipio` a partir do dump legado
 * (database/data/cedec_municipio.sql). Essa tabela e a ponte entre o
 * `municipio_id` legado (usado por legado_rat e outros dados antigos) e a
 * tabela `municipios` do NewSDC: cedec_municipio.Codmundv = municipios.codigo_ibge.
 *
 * Importa um subconjunto seguro de colunas (id, redec_id, nome, rpm e codigos
 * IBGE). Idempotente por `id` legado.
 */
class ImportCedecMunicipioCommand extends Command
{
    protected $signature = 'legado:importar-cedec-municipio
                            {--file= : Caminho do dump (padrao: database/data/cedec_municipio.sql)}
                            {--chunk=500 : Tamanho do lote de upsert}
                            {--truncate : Limpa a tabela antes de importar}
                            {--dry-run : Simula sem persistir}';

    protected $description = 'Importa cedec_municipio (ponte municipio_id legado -> IBGE -> municipios).';

    /**
     * Colunas string persistidas e seus limites (Postgres). Apenas o essencial
     * para a ponte de municipio: nome (exibicao), rpm (REDEC) e Codmundv (IBGE).
     *
     * @var array<string, int>
     */
    private const COLUNAS_STRING = ['nome' => 70, 'rpm' => 191, 'Codmundv' => 10];

    public function handle(MysqlDumpReader $reader): int
    {
        $file = (string) ($this->option('file') ?: database_path('data/cedec_municipio.sql'));
        $chunk = max(1, (int) $this->option('chunk'));
        $dryRun = (bool) $this->option('dry-run');

        if (! is_file($file)) {
            $this->error("Dump nao encontrado: {$file}");

            return self::FAILURE;
        }

        if ($this->option('truncate') && ! $dryRun) {
            DB::table('cedec_municipio')->delete();
            $this->line('Tabela cedec_municipio limpa.');
        }

        $lote = [];
        $lidos = 0;
        $importados = 0;
        $erros = 0;

        try {
            $reader->eachRow($file, function (array $row) use (&$lote, &$lidos, &$importados, &$erros, $chunk, $dryRun): void {
                $lidos++;
                try {
                    $registro = $this->mapear($row);
                    if ($registro !== null) {
                        $lote[] = $registro;
                    }
                } catch (Throwable $e) {
                    $erros++;
                    $this->warn("Linha {$lidos} ignorada: {$e->getMessage()}");
                }

                if (count($lote) >= $chunk) {
                    $importados += $this->flush($lote, $dryRun);
                    $lote = [];
                }
            }, 'cedec_municipio');

            if ($lote !== []) {
                $importados += $this->flush($lote, $dryRun);
            }
        } catch (Throwable $e) {
            $this->error("Falha na importacao: {$e->getMessage()}");

            return self::FAILURE;
        }

        $this->info(sprintf('Concluido: lidos=%d  importados=%d  erros=%d', $lidos, $importados, $erros));

        return self::SUCCESS;
    }

    /**
     * @param  array<int, array<string, mixed>>  $lote
     */
    private function flush(array $lote, bool $dryRun): int
    {
        if ($dryRun) {
            return count($lote);
        }

        DB::table('cedec_municipio')->upsert($lote, ['id'], ['redec_id', 'nome', 'rpm', 'Codmundv']);

        return count($lote);
    }

    /**
     * @param  array<string, string|null>  $row
     * @return array<string, mixed>|null
     */
    private function mapear(array $row): ?array
    {
        $id = isset($row['id']) && trim((string) $row['id']) !== '' ? (int) $row['id'] : null;
        if ($id === null) {
            return null;
        }

        $redec = isset($row['redec_id']) && trim((string) $row['redec_id']) !== '' ? (int) $row['redec_id'] : null;

        $registro = ['id' => $id, 'redec_id' => $redec];

        foreach (self::COLUNAS_STRING as $coluna => $limite) {
            $valor = $row[$coluna] ?? null;
            $registro[$coluna] = ($valor === null) ? null : mb_substr(trim($valor), 0, $limite);
        }

        return $registro;
    }
}
