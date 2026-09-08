<?php

declare(strict_types=1);

namespace App\Modules\Geoespacial\Console;

use App\Modules\Geoespacial\Services\CicloDeVidaDaCamada;
use Illuminate\Console\Command;

/**
 * Retira do mapa as camadas cuja validade venceu.
 *
 * Existe porque `valido_ate` era gravado no envio, exibido na tela e IGNORADO
 * por todo o resto: nem gold.geo_feicao_mapa nem a consulta do mapa filtravam
 * por ele. Uma camada valida ate 28/02 seguia desenhada no mapa de plantao
 * indefinidamente, e area de risco vencida apresentada como vigente e pior que
 * area faltando -- ninguem desconfia dela.
 */
class ArquivarVencidasCommand extends Command
{
    protected $signature = 'geoespacial:arquivar-vencidas';

    protected $description = 'Arquiva as camadas de risco cuja validade (valido_ate) venceu';

    public function handle(CicloDeVidaDaCamada $ciclo): int
    {
        $total = $ciclo->arquivarVencidas();

        if ($total === 0) {
            $this->info('Nenhuma camada com validade vencida.');

            return self::SUCCESS;
        }

        $this->info(sprintf('%d camada(s) arquivada(s) por validade vencida.', $total));

        return self::SUCCESS;
    }
}
