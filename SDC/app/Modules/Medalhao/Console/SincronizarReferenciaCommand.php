<?php

declare(strict_types=1);

namespace App\Modules\Medalhao\Console;

use App\Modules\Medalhao\Database\SincronizadorReferencia;
use App\Support\Database\ConexaoGeo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SincronizarReferenciaCommand extends Command
{
    protected $signature = 'geoespacial:sincronizar-referencia';

    protected $description = 'Sincroniza referencia.municipios do geoespacial com public.municipios do sdc';

    public function handle(SincronizadorReferencia $sincronizador): int
    {
        // Separado: copia para o banco dedicado. Desligado: referencia vive no
        // proprio sdc e so e relida de public.municipios.
        $r = $sincronizador->sincronizar(DB::connection(), ConexaoGeo::conexao());

        $this->info(sprintf(
            'referencia.municipios: %d registro(s), %s.',
            $r['registros'],
            $r['alterou'] ? 'atualizado' : 'sem mudanca',
        ));

        return self::SUCCESS;
    }
}
