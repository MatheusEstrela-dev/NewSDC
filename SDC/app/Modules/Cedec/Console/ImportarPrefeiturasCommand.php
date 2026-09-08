<?php

declare(strict_types=1);

namespace App\Modules\Cedec\Console;

use App\Modules\Compdec\Services\PrefeituraService;
use Illuminate\Console\Command;

final class ImportarPrefeiturasCommand extends Command
{
    protected $signature = 'cedec:importar-prefeituras {--chunk=100} {--dry-run}';

    protected $description = 'Migra/atualiza compdec_prefeituras a partir do legado gestaocedec (cedec_municipio + cedec_prefeitura).';

    public function handle(PrefeituraService $service): int
    {
        $chunk = max(1, (int) $this->option('chunk'));
        $dryRun = (bool) $this->option('dry-run');

        $this->info($dryRun
            ? 'Executando cedec:importar-prefeituras em modo --dry-run (nenhuma escrita sera feita).'
            : 'Executando cedec:importar-prefeituras.');

        $report = $service->migrarLegado($chunk, $dryRun);

        $this->table(
            ['Metrica', 'Valor'],
            [
                ['Total processado', (string) $report->total()],
                ['Inseridos', (string) $report->inseridos],
                ['Atualizados', (string) $report->atualizados],
                ['Ignorados', (string) $report->ignorados],
                ['Erros', (string) $report->erros],
            ],
        );

        foreach ($report->errosDetalhes as $erro) {
            $this->warn(sprintf('legacy_id=%s: %s', $erro['legacy_id'] ?? 'null', $erro['motivo']));
        }

        return self::SUCCESS;
    }
}
