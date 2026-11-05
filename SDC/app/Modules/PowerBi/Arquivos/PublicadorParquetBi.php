<?php

declare(strict_types=1);

namespace App\Modules\PowerBi\Arquivos;

use App\Modules\PowerBi\Support\DefinicaoTabela;
use Flow\Parquet\ParquetFile\Schema;
use Flow\Parquet\ParquetFile\Schema\FlatColumn;
use Flow\Parquet\Reader;
use Flow\Parquet\Writer;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

/**
 * Gera Parquet de uma tabela bi_* em streaming (mesmo padrao do
 * ArquivadorParquetSilver do medalhao): arquivo temporario, contagem relida
 * antes de devolver, envio ao disco powerbi por stream.
 */
final class PublicadorParquetBi
{
    public function __construct(private readonly LeitorTabelaBi $leitor)
    {
    }

    /**
     * Escreve o Parquet num temporario local e confere a contagem lida de volta.
     * Quem chama apaga o temporario.
     *
     * @return array{temporario: string, linhas: int}
     */
    public function gerarLocal(DefinicaoTabela $d): array
    {
        $tipos = $this->leitor->tipos($d);
        $temporario = sprintf('%s/powerbi-%s-%s-%s.parquet', sys_get_temp_dir(), $d->modulo, $d->nome, bin2hex(random_bytes(8)));

        try {
            $writer = new Writer();
            $writer->open($temporario, $this->schema($tipos));

            $linhas = 0;
            foreach ($this->leitor->lotes($d, $tipos, (int) config('powerbi.lote', 1000)) as $lote) {
                $writer->writeBatch($lote);
                $linhas += count($lote);
            }
            $writer->close();

            $lidas = (new Reader())->read($temporario)->metadata()->rowsNumber();
            if (! is_file($temporario) || $lidas !== $linhas) {
                throw new RuntimeException("Parquet de {$d->alvo()} divergente: gravadas {$linhas}, lidas {$lidas}.");
            }

            return ['temporario' => $temporario, 'linhas' => $linhas];
        } catch (Throwable $e) {
            is_file($temporario) && @unlink($temporario);

            throw $e;
        }
    }

    /** Envia um arquivo local ao disco powerbi e confirma a existencia. */
    public function enviar(string $local, string $relativo): void
    {
        $disco = Storage::disk(RegistroDeArquivos::disco());
        $fluxo = fopen($local, 'rb');
        try {
            $disco->writeStream($relativo, $fluxo);
        } finally {
            is_resource($fluxo) && fclose($fluxo);
        }
        if (! $disco->exists($relativo)) {
            throw new RuntimeException("Arquivo nao encontrado apos a escrita: {$relativo}");
        }
    }

    /** @param array<string, string> $tipos */
    private function schema(array $tipos): Schema
    {
        $colunas = [];
        foreach ($tipos as $nome => $tipo) {
            $colunas[] = match ($tipo) {
                'int' => FlatColumn::int64($nome),
                'bool' => FlatColumn::boolean($nome),
                default => FlatColumn::string($nome),
            };
        }

        return Schema::with(...$colunas);
    }
}
