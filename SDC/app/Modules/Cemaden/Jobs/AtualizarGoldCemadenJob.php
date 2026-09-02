<?php

declare(strict_types=1);

namespace App\Modules\Cemaden\Jobs;

use App\Modules\Medalhao\Events\GoldAtualizado;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\DB;

class AtualizarGoldCemadenJob implements ShouldQueue
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
        // A cada 10 minutos o refresh e mais frequente que no INMET, entao a
        // guarda contra sobreposicao importa mais aqui: dois refresh da mesma
        // matview so competem por I/O.
        return [(new WithoutOverlapping('gold-cemaden'))->expireAfter(600)];
    }

    public function handle(): void
    {
        // CONCURRENTLY exige o indice unico da migration e nao roda dentro de
        // transacao — este job nao pode ser chamado de dentro de
        // DB::transaction().
        //
        // A ordem importa: cemaden_estatisticas le cemaden_mapa, entao o mapa
        // vem primeiro, senao as estatisticas ficam um ciclo atrasadas.
        DB::statement('REFRESH MATERIALIZED VIEW CONCURRENTLY gold.cemaden_mapa');
        DB::statement('REFRESH MATERIALIZED VIEW CONCURRENTLY gold.cemaden_estatisticas');

        // Avisa DEPOIS do refresh, nao na ingestao: e o refresh que torna o dado
        // visivel.
        GoldAtualizado::dispatch('cemaden');
    }
}
