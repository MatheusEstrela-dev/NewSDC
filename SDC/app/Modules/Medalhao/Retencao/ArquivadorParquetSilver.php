<?php

declare(strict_types=1);

namespace App\Modules\Medalhao\Retencao;

use Carbon\Carbon;
use Flow\Parquet\ParquetFile\Schema;
use Flow\Parquet\ParquetFile\Schema\FlatColumn;
use Flow\Parquet\Writer;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

/**
 * Grava um mes de leituras silver em Parquet no disco do medalhao, no mesmo
 * padrao do FlowParquetArquivador do bronze (Writer do flow-php, arquivo
 * temporario, verificacao antes de devolver), mas com schema derivado da
 * tabela e escrita em streaming: um mes de leituras tem milhoes de linhas e
 * nao cabe em memoria como o payload bruto de um dia.
 *
 * Quem chama so descarta o dado depois que este metodo retorna: qualquer falha
 * sobe como excecao.
 */
final class ArquivadorParquetSilver
{
    private const TIPOS_INTEIROS = ['bigint', 'integer', 'smallint'];

    /**
     * @param array<string, string> $colunas nome => data_type do information_schema
     * @param iterable<list<object>> $lotes linhas agrupadas, em ordem
     * @return array{arquivo: string, linhas: int}
     */
    public function arquivar(string $tabela, string $mes, array $colunas, iterable $lotes): array
    {
        $disco = (string) config('medalhao.disco');
        $relativo = sprintf('silver/tabela=%s/mes=%s/parte-0.parquet', $tabela, $mes);
        $temporario = sprintf('%s/medalhao-%s-%s.parquet', sys_get_temp_dir(), $tabela, bin2hex(random_bytes(8)));

        try {
            $writer = new Writer();
            $writer->open($temporario, $this->schema($colunas));

            $linhas = 0;
            foreach ($lotes as $lote) {
                $writer->writeBatch(array_map(fn (object $l): array => $this->converter($colunas, $l), $lote));
                $linhas += count($lote);
            }

            $writer->close();

            if ($linhas === 0 || ! is_file($temporario) || filesize($temporario) === 0) {
                throw new RuntimeException("Parquet vazio para {$tabela} em {$mes}.");
            }

            $fluxo = fopen($temporario, 'rb');
            Storage::disk($disco)->writeStream($relativo, $fluxo);
            is_resource($fluxo) && fclose($fluxo);

            if (! Storage::disk($disco)->exists($relativo)) {
                throw new RuntimeException("Parquet nao encontrado apos a escrita: {$relativo}");
            }

            return ['arquivo' => $relativo, 'linhas' => $linhas];
        } catch (Throwable $e) {
            throw $e instanceof RuntimeException
                ? $e
                : new RuntimeException("Falha ao arquivar {$tabela} {$mes} em Parquet: {$e->getMessage()}", 0, $e);
        } finally {
            if (is_file($temporario)) {
                @unlink($temporario);
            }
        }
    }

    /** @param array<string, string> $colunas */
    private function schema(array $colunas): Schema
    {
        $planas = [];

        foreach ($colunas as $nome => $tipo) {
            $planas[] = in_array($tipo, self::TIPOS_INTEIROS, true)
                ? FlatColumn::int64($nome)
                : FlatColumn::string($nome);
        }

        return Schema::with(...$planas);
    }

    /**
     * Inteiros ficam inteiros; o resto vira texto (numeric sem perda de
     * precisao, timestamptz em ISO-8601 UTC), legivel por pandas e Power BI.
     *
     * @param array<string, string> $colunas
     * @return array<string, int|string|null>
     */
    private function converter(array $colunas, object $linha): array
    {
        $saida = [];

        foreach ($colunas as $nome => $tipo) {
            $valor = $linha->{$nome};

            $saida[$nome] = match (true) {
                $valor === null => null,
                in_array($tipo, self::TIPOS_INTEIROS, true) => (int) $valor,
                $tipo === 'timestamp with time zone' => Carbon::parse((string) $valor)->utc()->toIso8601String(),
                default => (string) $valor,
            };
        }

        return $saida;
    }
}
