<?php

declare(strict_types=1);

namespace App\Modules\PowerBi\Console;

use App\Modules\PowerBi\Support\CatalogoBi;
use App\Modules\PowerBi\Support\ExecutorMaterializacao;
use App\Modules\PowerBi\Support\VerificadorParidade;
use App\Support\Database\ConexaoPowerBi;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

final class MaterializarCommand extends Command
{
    protected $signature = 'powerbi:materializar
        {modulo? : Modulo (omita para todos os registrados)}
        {--desde= : Forca o cursor para esta data (reprocessa)}
        {--verificar : Apos materializar, compara com o service ao vivo (paridade por hash)}
        {--seco : Roda e reverte (nada persiste)}';

    protected $description = 'Materializa as tabelas bi_* do sdc_powerbi a partir da fonte';

    public function handle(CatalogoBi $catalogo, ExecutorMaterializacao $executor, VerificadorParidade $verificador): int
    {
        if (! ConexaoPowerBi::ativa()) {
            $this->error('sdc_powerbi desligado (DB_POWERBI_CONNECTION).');

            return self::FAILURE;
        }

        $modulos = $this->argument('modulo') ? [(string) $this->argument('modulo')] : $catalogo->modulos();
        $desde = $this->option('desde') ? Carbon::parse((string) $this->option('desde')) : null;
        $falhou = false;

        foreach ($modulos as $modulo) {
            $m = $catalogo->materializador($modulo);
            if ($m === null) {
                $this->error("Modulo {$modulo} sem materializador registrado.");
                $falhou = true;

                continue;
            }

            $r = $executor->materializar($m, $desde, (bool) $this->option('seco'));
            $this->info(sprintf(
                '%s%s: lidas=%d gravadas=%d inalteradas=%d removidas=%d',
                $modulo, $this->option('seco') ? ' (seco)' : '', $r->lidas(), $r->gravadas(), $r->inalteradas(), $r->removidas()
            ));

            if ($this->option('verificar') && ! $this->option('seco')) {
                foreach ($verificador->verificar($m) as $tabela => $v) {
                    $ok = $v['divergentes'] + $v['faltantes'] + $v['sobrando'] === 0;
                    $falhou = $falhou || ! $ok;
                    $this->line(sprintf(
                        '  verificar %s.%s: ao_vivo=%d materializado=%d divergentes=%d faltantes=%d sobrando=%d [%s]',
                        $modulo, $tabela, $v['ao_vivo'], $v['materializado'], $v['divergentes'], $v['faltantes'], $v['sobrando'], $ok ? 'OK' : 'DIVERGE'
                    ));
                }
            }
        }

        return $falhou ? self::FAILURE : self::SUCCESS;
    }
}
