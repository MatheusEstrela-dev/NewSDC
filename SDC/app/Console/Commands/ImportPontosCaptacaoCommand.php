<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Modules\Tdap\Models\PontoCaptacao;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * ETL legado (gestaocedec.pip_ponto_cap) -> novo (pip_pmda_ponto).
 *
 * Le os pontos de captacao do banco legado via connection "legacy" e popula a
 * tabela pip_pmda_ponto preservando o id legado (id_ponto) para manter
 * referencias estaveis. Idempotente (upsert por id). Linhas cujo municipio nao
 * existe no schema novo sao ignoradas e reportadas.
 */
class ImportPontosCaptacaoCommand extends Command
{
    protected $signature = 'tdap:import-pontos-captacao
                            {--dry-run : Simula sem persistir}
                            {--chunk=500 : Tamanho do chunk de leitura}';

    protected $description = 'Importa pontos de captacao do banco legado (pip_ponto_cap) para pip_pmda_ponto.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $chunk = (int) $this->option('chunk');

        if (! $this->legacyDisponivel()) {
            $this->error('Connection "legacy" indisponivel. Configure DB_LEGACY_* no .env.');

            return self::FAILURE;
        }

        $municipiosValidos = DB::table('municipios')->pluck('id')->flip();

        $total = 0;
        $importados = 0;
        $ignorados = 0;

        try {
            DB::connection('legacy')
                ->table('pip_ponto_cap')
                ->orderBy('id_ponto')
                ->chunk($chunk, function ($linhas) use (&$total, &$importados, &$ignorados, $municipiosValidos, $dryRun): void {
                    foreach ($linhas as $linha) {
                        $total++;
                        $municipioId = (int) $linha->id_municipio;

                        if (! $municipiosValidos->has($municipioId)) {
                            $ignorados++;
                            $this->warn("Ponto {$linha->id_ponto} ignorado: municipio {$municipioId} inexistente.");

                            continue;
                        }

                        if (! $dryRun) {
                            PontoCaptacao::withTrashed()->updateOrCreate(
                                ['id' => (int) $linha->id_ponto],
                                [
                                    'municipio_id' => $municipioId,
                                    'nome'         => mb_strtoupper(trim((string) $linha->nome)),
                                    'tipo'         => $this->normalizarTipo($linha->tipo),
                                    'latitude'     => $this->nullable($linha->latitude),
                                    'longitude'    => $this->nullable($linha->longitude),
                                    'capacidade'   => (float) ($linha->capacidade ?? 0),
                                    'ativo'        => true,
                                    'deleted_at'   => null,
                                ],
                            );
                        }

                        $importados++;
                    }
                });
        } catch (Throwable $e) {
            $this->error("Falha na importacao: {$e->getMessage()}");

            return self::FAILURE;
        }

        $this->info(sprintf(
            '== Pontos de captacao ==  lidos=%d  importados=%d  ignorados=%d  %s',
            $total,
            $importados,
            $ignorados,
            $dryRun ? '(dry-run)' : '',
        ));

        return self::SUCCESS;
    }

    private function legacyDisponivel(): bool
    {
        try {
            DB::connection('legacy')->getPdo();

            return DB::connection('legacy')->getSchemaBuilder()->hasTable('pip_ponto_cap');
        } catch (Throwable) {
            return false;
        }
    }

    private function normalizarTipo(mixed $tipo): int
    {
        $valor = (int) $tipo;

        return ($valor >= 1 && $valor <= 6) ? $valor : 1;
    }

    private function nullable(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $str = trim((string) $value);

        return $str === '' ? null : $str;
    }
}
