<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\Legacy\MysqlDumpReader;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Importa o arquivo morto do RAT legado a partir do dump MySQL `com_rat.sql`
 * (database/data/com_rat.sql) para a tabela Postgres `legado_rat`.
 *
 * Nao ha banco MySQL vivo neste ambiente, entao o dump e lido em streaming via
 * MysqlDumpReader e inserido em lotes com upsert idempotente por `id` legado.
 */
class ImportRatArquivadosCommand extends Command
{
    protected $signature = 'rat:importar-arquivados
                            {--file= : Caminho do dump (padrao: database/data/com_rat.sql)}
                            {--chunk=1000 : Tamanho do lote de upsert}
                            {--truncate : Limpa a tabela antes de importar}
                            {--dry-run : Simula sem persistir}';

    protected $description = 'Importa o RAT legado (dump com_rat.sql) para a tabela legado_rat (arquivo morto, somente leitura).';

    /** @var array<int, string> Colunas do dump persistidas em legado_rat (mapeamento 1:1 por nome). */
    private const COLUNAS_PERSISTIDAS = [
        'id', 'num_ocorrencia', 'dt_ocorrencia', 'municipio_id', 'operador_id',
        'ocorrencia_id', 'alvo_id', 'cobrade_id', 'lugar_descricao', 'envolvidos',
        'nome_operacao', 'endereco', 'numero', 'bairro', 'estado', 'referencia',
        'cep', 'acoes', 'updated_at', 'created_at', 'operador_nome',
    ];

    /** @var array<int, string> Colunas de data (viram null quando vazias/zeradas). */
    private const COLUNAS_DATA = ['dt_ocorrencia', 'created_at', 'updated_at'];

    /** @var array<int, string> Colunas inteiras (FK) que viram null quando vazias. */
    private const COLUNAS_INT = ['id', 'municipio_id', 'operador_id', 'ocorrencia_id', 'alvo_id', 'cobrade_id'];

    public function handle(MysqlDumpReader $reader): int
    {
        $file = (string) ($this->option('file') ?: database_path('data/com_rat.sql'));
        $chunk = max(1, (int) $this->option('chunk'));
        $dryRun = (bool) $this->option('dry-run');

        if (! is_file($file)) {
            $this->error("Dump nao encontrado: {$file}");

            return self::FAILURE;
        }

        $this->info(sprintf('== Import RAT arquivo morto ==  chunk=%d  %s', $chunk, $dryRun ? '(dry-run)' : ''));

        if ($this->option('truncate') && ! $dryRun) {
            DB::table('legado_rat')->truncate();
            $this->line('Tabela legado_rat truncada.');
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
                    $this->output->write("\r  lidos={$lidos} importados={$importados} erros={$erros}   ");
                }
            }, 'com_rat');

            if ($lote !== []) {
                $importados += $this->flush($lote, $dryRun);
            }
        } catch (Throwable $e) {
            $this->newLine();
            $this->error("Falha na importacao: {$e->getMessage()}");

            return self::FAILURE;
        }

        $this->newLine();
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

        $updateCols = array_values(array_filter(
            self::COLUNAS_PERSISTIDAS,
            static fn (string $c): bool => $c !== 'id',
        ));

        DB::table('legado_rat')->upsert($lote, ['id'], $updateCols);

        return count($lote);
    }

    /**
     * Mapeia a linha do dump (coluna => string|null) para o registro persistido.
     *
     * @param  array<string, string|null>  $row
     * @return array<string, mixed>|null
     */
    private function mapear(array $row): ?array
    {
        $registro = [];

        foreach (self::COLUNAS_PERSISTIDAS as $coluna) {
            if (! array_key_exists($coluna, $row)) {
                continue;
            }

            $valor = $row[$coluna];

            if (in_array($coluna, self::COLUNAS_INT, true)) {
                $registro[$coluna] = ($valor === null || trim($valor) === '') ? null : (int) $valor;
            } elseif (in_array($coluna, self::COLUNAS_DATA, true)) {
                $registro[$coluna] = $this->normalizarData($valor);
            } else {
                $registro[$coluna] = $valor;
            }
        }

        if (($registro['id'] ?? null) === null) {
            return null;
        }

        if (! array_key_exists('num_ocorrencia', $registro) || $registro['num_ocorrencia'] === null) {
            $registro['num_ocorrencia'] = '0';
        }

        return $registro;
    }

    /**
     * Datas do legado podem vir vazias, "NULL", zeradas ("0000-00-00 ...") ou
     * com espaco a esquerda. Tudo isso vira null para o Postgres.
     */
    private function normalizarData(?string $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        $v = trim($valor);

        if ($v === '' || strcasecmp($v, 'NULL') === 0 || str_starts_with($v, '0000-00-00')) {
            return null;
        }

        return $v;
    }
}
