<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Enums;

enum TipoLocalAbrigo: string
{
    case GINASIO = 'ginasio';
    case ESCOLA = 'escola';
    case IGREJA = 'igreja';
    case CENTRO_COMUNITARIO = 'centro_comunitario';
    case GALPAO = 'galpao';
    case OUTRO = 'outro';

    public static function toSelectArray(): array
    {
        return [
            ['value' => self::GINASIO->value, 'label' => 'Ginasio'],
            ['value' => self::ESCOLA->value, 'label' => 'Escola'],
            ['value' => self::IGREJA->value, 'label' => 'Igreja'],
            ['value' => self::CENTRO_COMUNITARIO->value, 'label' => 'Centro Comunitario'],
            ['value' => self::GALPAO->value, 'label' => 'Galpao'],
            ['value' => self::OUTRO->value, 'label' => 'Outro'],
        ];
    }
}
