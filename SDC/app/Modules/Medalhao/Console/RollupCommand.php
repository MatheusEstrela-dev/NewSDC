<?php

declare(strict_types=1);

namespace App\Modules\Medalhao\Console;

use App\Modules\Medalhao\Jobs\RolloverParquetJob;
use Illuminate\Console\Command;

class RollupCommand extends Command
{
    protected $signature = 'medalhao:rollup';

    protected $description = 'Arquiva a camada Bronze vencida em Parquet e poda o Postgres';

    public function handle(): int
    {
        RolloverParquetJob::dispatch();

        $this->info('Rollup da camada Bronze despachado para a fila medalhao.');

        return self::SUCCESS;
    }
}
