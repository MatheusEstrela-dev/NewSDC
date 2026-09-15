<?php

declare(strict_types=1);

namespace App\Modules\Inmet\Jobs;

use App\Modules\Medalhao\Events\GoldAtualizado;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\DB;

class AtualizarGoldInmetJob implements ShouldQueue
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
        return [(new WithoutOverlapping('gold-inmet'))->expireAfter(600)];
    }

    public function handle(): void
    {
        // CONCURRENTLY exige o indice unico da migration e nao roda dentro de
        // transacao — este job nao pode ser chamado de dentro de
        // DB::transaction().
        //
        // A ordem importa: inmet_estatisticas le inmet_mapa, entao o mapa vem
        // primeiro, senao as estatisticas ficam um ciclo atrasadas.
        DB::statement('REFRESH MATERIALIZED VIEW CONCURRENTLY gold.inmet_mapa');
        DB::statement('REFRESH MATERIALIZED VIEW CONCURRENTLY gold.inmet_estatisticas');

        // Avisa DEPOIS do refresh, nao na ingestao: e o refresh que torna o dado
        // visivel. Avisar antes faria o cliente rebuscar o estado anterior e a
        // tela piscaria sem mudar nada.
        GoldAtualizado::dispatch('inmet');
    }
}
