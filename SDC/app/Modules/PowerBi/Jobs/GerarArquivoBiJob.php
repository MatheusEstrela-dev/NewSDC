<?php

declare(strict_types=1);

namespace App\Modules\PowerBi\Jobs;

use App\Jobs\Concerns\TracksAsyncProgress;
use App\Models\RequestTrace;
use App\Modules\PowerBi\Arquivos\LeitorTabelaBi;
use App\Modules\PowerBi\Arquivos\PublicadorParquetBi;
use App\Modules\PowerBi\Arquivos\RegistroDeArquivos;
use App\Modules\PowerBi\Support\CatalogoBi;
use App\Modules\PowerBi\Support\DefinicaoTabela;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;
use Throwable;

/**
 * Arquivo sob demanda (csv ou parquet) de uma tabela bi_*, gerado na fila do
 * BI e entregue pelo download do trace (GET /api/v1/traces/{id}/download).
 */
final class GerarArquivoBiJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use TracksAsyncProgress;

    public const FORMATOS = ['csv', 'parquet'];

    public int $timeout = 900;

    public int $tries = 1;

    public function __construct(
        string $traceId,
        public readonly string $modulo,
        public readonly string $tabela,
        public readonly string $formato,
    ) {
        $this->setTrace($traceId);
        $this->onConnection((string) config('powerbi.arquivos.conexao_fila', 'redis-powerbi'));
    }

    public function handle(CatalogoBi $catalogo, LeitorTabelaBi $leitor, PublicadorParquetBi $publicador): void
    {
        $this->runTracked(function () use ($catalogo, $leitor, $publicador): array {
            $d = $catalogo->definicao($this->modulo, $this->tabela);
            if ($d === null || ! in_array($this->formato, self::FORMATOS, true)) {
                throw new RuntimeException('Tabela ou formato fora do catalogo.');
            }

            $relativo = sprintf(
                '%s/%s/%s/%s.%s',
                (string) config('powerbi.arquivos.prefixo_sob_demanda', 'sob-demanda'),
                $d->modulo,
                $d->nome,
                $this->traceId ?? bin2hex(random_bytes(8)),
                $this->formato
            );

            $local = $this->formato === 'parquet'
                ? $publicador->gerarLocal($d)['temporario']
                : $this->gerarCsv($d, $leitor);

            try {
                $publicador->enviar($local, $relativo);
            } finally {
                @unlink($local);
            }

            return ['disk' => RegistroDeArquivos::disco(), 'path' => $relativo];
        });
    }

    public function failed(Throwable $e): void
    {
        if ($this->traceId) {
            RequestTrace::find($this->traceId)?->markAsFailed($e->getMessage());
        }
    }

    /** CSV (;, UTF-8 com BOM, como os exports existentes) num temporario local. */
    private function gerarCsv(DefinicaoTabela $d, LeitorTabelaBi $leitor): string
    {
        $tipos = $leitor->tipos($d);
        $temporario = sprintf('%s/powerbi-%s-%s-%s.csv', sys_get_temp_dir(), $d->modulo, $d->nome, bin2hex(random_bytes(8)));
        $fh = fopen($temporario, 'wb');
        if ($fh === false) {
            throw new RuntimeException("Falha ao abrir {$temporario} para escrita.");
        }

        try {
            fwrite($fh, "\xEF\xBB\xBF");
            fputcsv($fh, array_keys($tipos), ';', '"', '');
            foreach ($leitor->lotes($d, $tipos, (int) config('powerbi.lote', 1000)) as $lote) {
                foreach ($lote as $linha) {
                    fputcsv($fh, array_map(fn (mixed $v): mixed => is_bool($v) ? ($v ? 'true' : 'false') : $v, $linha), ';', '"', '');
                }
            }
        } catch (Throwable $e) {
            fclose($fh);
            @unlink($temporario);

            throw $e;
        }
        fclose($fh);

        return $temporario;
    }
}
