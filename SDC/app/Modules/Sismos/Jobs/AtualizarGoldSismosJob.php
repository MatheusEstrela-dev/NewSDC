<?php

declare(strict_types=1);

namespace App\Modules\Sismos\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\DB;

class AtualizarGoldSismosJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public int $timeout = 300;

    public int $tries = 3;

    public function __construct()
    {
        $this->onQueue('medalhao');
    }

    /** @return array<int, object> */
    public function middleware(): array
    {
        // Dois refresh concorrentes da mesma matview nao trazem beneficio e so
        // competem por I/O.
        return [(new WithoutOverlapping('gold-sismos'))->expireAfter(600)];
    }

    public function handle(): void
    {
        // CONCURRENTLY evita travar a leitura do mapa durante a atualizacao.
        // Exige o indice unico criado na migration, e nao pode rodar dentro de
        // transacao — por isso este job nao deve ser chamado de dentro de um
        // DB::transaction().
        DB::statement('REFRESH MATERIALIZED VIEW CONCURRENTLY gold.sismos_mapa');
        DB::statement('REFRESH MATERIALIZED VIEW CONCURRENTLY gold.sismos_estatisticas');
    }
}
