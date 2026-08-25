<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Modules\Pmda\Services\ComunidadeLegadoService;
use Illuminate\Console\Command;

/**
 * Traz o catalogo de comunidades do legado (pip_comunidade) para `comunidades`.
 *
 * Mesmo desenho de compdec:migrar-legado - dry-run por padrao seguro,
 * confirmacao antes de persistir e MigracaoReport no final.
 */
class MigrarComunidadesPmdaLegadoCommand extends Command
{
    protected $signature = 'pmda:migrar-comunidades-legado
                            {--dry-run : Simula sem persistir}
                            {--chunk=500 : Tamanho do lote de leitura no legado}';

    protected $description = 'Migra o catalogo de comunidades do legado (pip_comunidade) para a tabela comunidades do PMDA.';

    public function handle(ComunidadeLegadoService $service): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $chunk = max(1, (int) $this->option('chunk'));

        $this->info(sprintf(
            '== ETL COMUNIDADES PMDA ==  dry-run=%s  chunk=%d',
            $dryRun ? 'sim' : 'nao',
            $chunk,
        ));

        if (! $dryRun && ! $this->confirm('Confirma migracao em modo persistente?', false)) {
            $this->warn('Cancelado.');

            return self::SUCCESS;
        }

        $report = $service->migrarLegado($chunk, $dryRun);

        $this->newLine();
        $this->table(
            ['recurso', 'total', 'inseridos', 'atualizados', 'ignorados', 'erros'],
            [[
                $report->recurso,
                $report->total(),
                $report->inseridos,
                $report->atualizados,
                $report->ignorados,
                $report->erros,
            ]],
        );

        // "ignorados" nao e falha: sao as comunidades de municipio sem par em
        // `municipios` e as duplicatas perdedoras do unique (municipio, nome).
        if ($report->erros > 0) {
            $this->newLine();
            $this->error("{$report->erros} linha(s) com erro:");

            foreach (array_slice($report->errosDetalhes, 0, 20) as $erro) {
                $this->line("  legacy_id={$erro['legacy_id']}  {$erro['motivo']}");
            }

            if (count($report->errosDetalhes) > 20) {
                $this->line('  ... (' . (count($report->errosDetalhes) - 20) . ' restantes)');
            }

            return self::FAILURE;
        }

        $this->newLine();
        $this->info($dryRun ? 'Simulacao concluida - nada foi gravado.' : 'Migracao concluida.');

        return self::SUCCESS;
    }
}
