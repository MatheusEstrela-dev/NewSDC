<?php

declare(strict_types=1);

namespace App\Modules\Cedec\Console;

use App\Modules\Cedec\Services\RedecSincronizacaoService;
use Illuminate\Console\Command;

final class SincronizarRedecsCommand extends Command
{
    protected $signature = 'cedec:sincronizar-redecs {--dry-run}';

    protected $description = 'Preenche cedec_municipio.redec_id a partir de cedec_rpm_mun no legado gestaocedec.';

    public function handle(RedecSincronizacaoService $service): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $this->info($dryRun
            ? 'Executando cedec:sincronizar-redecs em modo --dry-run (nenhuma escrita sera feita).'
            : 'Executando cedec:sincronizar-redecs.');

        $report = $service->sincronizar($dryRun);

        $this->table(
            ['Metrica', 'Valor'],
            [
                ['Total processado', (string) $report->total()],
                ['Atualizados', (string) $report->atualizados],
                ['Ja corretos / sem espelho', (string) $report->ignorados],
                ['Erros', (string) $report->erros],
            ],
        );

        foreach ($report->errosDetalhes as $erro) {
            $this->warn(sprintf('id_municipio=%s: %s', $erro['legacy_id'] ?? 'null', $erro['motivo']));
        }

        return self::SUCCESS;
    }
}
