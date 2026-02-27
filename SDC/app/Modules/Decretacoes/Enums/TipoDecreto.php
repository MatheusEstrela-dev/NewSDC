<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes\Enums;

enum TipoDecreto: string
{
    case SE = 'se';
    case ECP = 'ecp';

    public static function toSelectArray(): array
    {
        return [
            self::SE->value => 'Situação de Emergência (SE)',
            self::ECP->value => 'Estado de Calamidade Pública (ECP)',
        ];
    }
}
