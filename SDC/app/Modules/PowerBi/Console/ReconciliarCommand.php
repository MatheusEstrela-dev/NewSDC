<?php

declare(strict_types=1);

namespace App\Modules\PowerBi\Console;

use App\Modules\PowerBi\Support\CatalogoBi;
use App\Modules\PowerBi\Support\ExecutorMaterializacao;
use App\Modules\PowerBi\Support\Reconciliador;
use App\Support\Database\ConexaoPowerBi;
use Illuminate\Console\Command;

final class ReconciliarCommand extends Command
{
    protected $signature = 'powerbi:reconciliar {modulo? : Modulo (omita para todos os registrados)}';

    protected $description = 'Marca como removido no sdc_powerbi o que sumiu da fonte (anti-join de chaves)';

    public function handle(CatalogoBi $catalogo, ExecutorMaterializacao $executor, Reconciliador $reconciliador): int
    {
        if (! ConexaoPowerBi::ativa()) {
            $this->error('sdc_powerbi desligado (DB_POWERBI_CONNECTION).');

            return self::FAILURE;
        }

        $modulos = $this->argument('modulo') ? [(string) $this->argument('modulo')] : $catalogo->modulos();

        foreach ($modulos as $modulo) {
            $m = $catalogo->materializador($modulo);
            if ($m === null) {
                $this->error("Modulo {$modulo} sem materializador registrado.");

                continue;
            }

            $porTabela = $executor->comLock('reconciliar', $modulo, fn (): array => $reconciliador->reconciliar($m));
            foreach ($porTabela as $tabela => $r) {
                $this->info(sprintf(
                    '%s.%s: origem=%d destino=%d removidas=%d divergentes=%d',
                    $modulo, $tabela, $r['origem'], $r['destino'], $r['removidas'], $r['divergentes']
                ));
            }
        }

        return self::SUCCESS;
    }
}
