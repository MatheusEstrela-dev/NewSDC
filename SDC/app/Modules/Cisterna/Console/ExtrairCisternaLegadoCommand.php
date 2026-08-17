<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Console;

use App\Modules\Cisterna\Domain\Etl\TabelasLegado;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Etapa 1 do ETL: espelha o legado MySQL em cisterna_legado_raw.doc jsonb.
 *
 * Deliberadamente **nao conhece o schema** das tabelas de origem. Faz
 * SELECT * e guarda a linha inteira. Isso remove a dependencia do
 * SHOW CREATE TABLE de producao (spec secao 7.4) desta etapa: coluna
 * inesperada aparece como chave a mais no doc, nao como erro.
 *
 * Mesmo padrao de AjudaHumanitaria\Console\ExtrairLegadoAjuCommand.
 */
class ExtrairCisternaLegadoCommand extends Command
{
    protected $signature = 'cisterna:extrair-legado
                            {--only= : Tabelas separadas por virgula (padrao: todas)}
                            {--chunk=500 : Linhas por lote}
                            {--truncar : Limpa cisterna_legado_raw antes de extrair}';

    protected $description = 'Extrai as tabelas do modulo Cisterna do legado sdc para cisterna_legado_raw (jsonb).';

    public function handle(): int
    {
        $tabelas = TabelasLegado::resolverSelecao($this->option('only'));

        if ($tabelas === []) {
            $this->error('Nenhuma tabela reconhecida em --only. Conhecidas: '
                .implode(', ', TabelasLegado::ORDEM_DE_CARGA));

            return self::FAILURE;
        }

        try {
            $legado = DB::connection('legado_cisterna_mysql');
            $legado->getPdo();
        } catch (Throwable $e) {
            $this->error('Nao foi possivel conectar ao legado: '.$e->getMessage());
            $this->line('Conferir LEGADO_CISTERNA_DB_* no .env.');

            return self::FAILURE;
        }

        if ($this->option('truncar')) {
            DB::table('cisterna_legado_raw')->whereIn('tabela', $tabelas)->delete();
            $this->line('cisterna_legado_raw limpa para: '.implode(', ', $tabelas));
        }

        $chunk = max(1, (int) $this->option('chunk'));
        $totalGeral = 0;

        foreach ($tabelas as $tabela) {
            $pk = TabelasLegado::CHAVE_PRIMARIA[$tabela];

            if (! $legado->getSchemaBuilder()->hasTable($tabela)) {
                $this->warn("Tabela ausente no legado, ignorada: {$tabela}");

                continue;
            }

            $total = 0;

            // orderBy na PK e obrigatorio para o chunkById nao repetir linha.
            $legado->table($tabela)->orderBy($pk)->chunkById(
                $chunk,
                function ($linhas) use ($tabela, $pk, &$total): void {
                    $agora = now();

                    $registros = $linhas->map(fn ($linha): array => [
                        'tabela' => $tabela,
                        'pk_legado' => (string) $linha->{$pk},
                        'doc' => json_encode((array) $linha, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE),
                        'extraido_em' => $agora,
                    ])->all();

                    // Idempotente: reextrair atualiza o doc em vez de estourar
                    // o unique (tabela, legacy_id).
                    DB::table('cisterna_legado_raw')->upsert(
                        $registros,
                        ['tabela', 'pk_legado'],
                        ['doc', 'extraido_em'],
                    );

                    $total += count($registros);
                },
                $pk,
            );

            $this->line(sprintf('%-32s %6d linha(s)', $tabela, $total));
            $totalGeral += $total;
        }

        $this->newLine();
        $this->info("Extracao concluida: {$totalGeral} linha(s) em cisterna_legado_raw.");
        $this->line('Proximo passo: artisan cisterna:refinar-legado --dry-run');

        return self::SUCCESS;
    }
}
