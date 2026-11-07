<?php

declare(strict_types=1);

namespace App\Modules\PowerBi\Support;

use App\Support\Database\ConexaoPowerBi;
use App\Support\Database\LeituraConsistentePostgres;
use Closure;

/** Mantem a mesma visao da fonte entre paginas e consultas relacionadas. */
final class LeituraConsistenteDaFonte
{
    /** O callback deve consumir seus iteraveis antes de devolver o resultado. */
    public static function executar(Closure $leitura): mixed
    {
        return LeituraConsistentePostgres::executar(ConexaoPowerBi::fonte(), $leitura);
    }
}
