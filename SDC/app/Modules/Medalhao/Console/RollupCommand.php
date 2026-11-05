<?php

declare(strict_types=1);

namespace App\Modules\Medalhao\Console;

use App\Modules\Medalhao\Jobs\RolloverParquetJob;
use App\Modules\Medalhao\Retencao\RetencaoSilver;
use Illuminate\Console\Command;

class RollupCommand extends Command
{
    protected $signature = 'medalhao:rollup';

    protected $description = 'Arquiva em Parquet o Bronze vencido e as leituras Silver acima da retencao, e poda o Postgres';

    public function handle(RetencaoSilver $retencaoSilver): int
    {
        RolloverParquetJob::dispatch();

        $this->info('Rollup da camada Bronze despachado para a fila medalhao.');

        foreach ($retencaoSilver->executar() as $mes) {
            $this->line(sprintf(
                'Silver %s %s: %d linha(s) %s.',
                $mes['tabela'],
                $mes['mes'],
                $mes['linhas'],
                $mes['arquivo'] === null ? 'sem dados, descartado' : "arquivadas em {$mes['arquivo']} e descartadas",
            ));
        }

        return self::SUCCESS;
    }
}
