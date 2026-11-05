<?php

declare(strict_types=1);

namespace App\Modules\Geoespacial\Jobs;

use App\Modules\Medalhao\Events\GoldAtualizado;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use App\Modules\Medalhao\Support\RefreshGold;
use App\Modules\Medalhao\Support\TabelasGeo;

class AtualizarGoldGeoJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public int $timeout = 600;

    public int $tries = 3;

    public function __construct()
    {
        $this->onQueue('medalhao');
    }

    /** @return array<int, object> */
    public function middleware(): array
    {
        return [(new WithoutOverlapping('gold-geoespacial'))->expireAfter(900)];
    }

    public function handle(): void
    {
        // CONCURRENTLY exige o indice unico da migration e nao roda dentro de
        // transacao.
        //
        // A ordem importa: geo_camada_municipios cruza a geometria das feicoes,
        // e o mapa vem primeiro para as duas refletirem o mesmo estado.
        RefreshGold::executar(TabelasGeo::feicaoMapa(), TabelasGeo::camadaMunicipios());

        GoldAtualizado::dispatch('geoespacial');
    }
}
