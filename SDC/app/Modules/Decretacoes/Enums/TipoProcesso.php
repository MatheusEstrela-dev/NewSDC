<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes\Enums;

enum TipoProcesso: string
{
    case MUNICIPAL = 'municipal';
    case ESTADUAL = 'estadual';

    public static function toSelectArray(): array
    {
        $array = [];
        foreach (self::cases() as $case) {
            $array[$case->value] = ucfirst($case->value);
        }
        return $array;
    }
}
