<?php

declare(strict_types=1);

namespace App\Modules\PowerBi\Support;

use App\Support\Database\ConexaoPowerBi;

/** Flag POWERBI_SAIDA_<MODULO>: o endpoint antigo le ao vivo ou do sdc_powerbi? */
final class SaidaBi
{
    public static function materializada(string $modulo): bool
    {
        return ConexaoPowerBi::ativa() && config("powerbi.saida.{$modulo}", 'ao_vivo') === 'materializado';
    }
}
