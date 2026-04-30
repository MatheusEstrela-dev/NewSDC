<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes\Constants;

final class DesastreConstants
{
    public const CATEGORIA_DANOS_HUMANOS_ID = 1;

    public const CAT_DANOS_MATERIAIS    = 'DANOS MATERIAIS';
    public const CAT_PREJUIZOS_PUBLICOS = 'PREJUÍZOS ECONÔMICOS PÚBLICOS';
    public const CAT_PREJUIZOS_PRIVADOS = 'PREJUÍZOS ECONÔMICOS PRIVADOS';

    public const DANOS_HUMANOS_MAP = [
        1 => 'obitos',
        2 => 'feridos',
        3 => 'feridos',
        4 => 'desabrigados',
        5 => 'desalojados',
        6 => 'desaparecidos',
        7 => 'outros_afetados',
    ];
}
