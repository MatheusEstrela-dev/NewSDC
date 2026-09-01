<?php

declare(strict_types=1);

namespace App\Modules\Medalhao\Infrastructure;

use App\Modules\Medalhao\Contracts\ArquivadorBronze;
use Carbon\CarbonInterface;
use Flow\Parquet\ParquetFile\Schema;
use Flow\Parquet\ParquetFile\Schema\FlatColumn;
use Flow\Parquet\Writer;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

final class FlowParquetArquivador implements ArquivadorBronze
{
    public function arquivar(string $fonte, CarbonInterface $dia, iterable $linhas): string
    {
        $disco = (string) config('medalhao.disco');

        $relativo = sprintf(
            'bronze/fonte=%s/dt=%s/parte-0.parquet',
            $fonte,
            $dia->format('Y-m-d')
        );

        // Writer::write() lanca se o arquivo ja existir, entao o caminho
        // temporario e apenas reservado, nunca criado (nada de tempnam aqui).
        $temporario = sprintf(
            '%s/medalhao-%s-%s.parquet',
            sys_get_temp_dir(),
            $fonte,
            bin2hex(random_bytes(8))
        );

        try {
            (new Writer())->write($temporario, $this->schema(), $linhas);

            $conteudo = @file_get_contents($temporario);

            if ($conteudo === false || $conteudo === '') {
                throw new RuntimeException("Parquet gerado vazio para {$fonte} em {$dia->format('Y-m-d')}.");
            }

            Storage::disk($disco)->put($relativo, $conteudo);

            if (! Storage::disk($disco)->exists($relativo)) {
                throw new RuntimeException("Parquet nao encontrado apos a escrita: {$relativo}");
            }

            return $relativo;
        } catch (Throwable $e) {
            // Sobe a falha: quem chama nao pode podar o Bronze sem arquivo.
            throw $e instanceof RuntimeException
                ? $e
                : new RuntimeException("Falha ao arquivar Bronze em Parquet: {$e->getMessage()}", 0, $e);
        } finally {
            if (is_file($temporario)) {
                @unlink($temporario);
            }
        }
    }

    /**
     * Espelha as colunas de bronze.ingestao_bruta, MENOS fonte. meta vai como
     * string JSON e os timestamps como ISO-8601: o objetivo e um arquivo que
     * pandas e Power BI leiam sem tratamento especial de tipo.
     *
     * fonte fica de fora de proposito. Ela ja e a chave da particao Hive no
     * caminho (bronze/fonte=<fonte>/), e o pyarrow, ao achar o mesmo nome nos
     * dois lugares, aborta a leitura do dataset com "Unable to merge: Field
     * fonte has incompatible types: string vs dictionary<...>" -- justamente
     * nos dois caminhos mais obvios, pq.ParquetDataset e pq.read_table. A
     * particao devolve a coluna na leitura, entao nada se perde.
     */
    private function schema(): Schema
    {
        return Schema::with(
            FlatColumn::int64('id'),
            FlatColumn::string('conteudo_bruto'),
            FlatColumn::string('formato'),
            FlatColumn::string('hash_conteudo'),
            FlatColumn::string('meta'),
            FlatColumn::string('coletado_em'),
            FlatColumn::string('processado_em'),
        );
    }
}
