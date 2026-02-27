<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Enums;

enum TipoMovimentacao: string
{
    case ENTRADA = 'entrada';
    case SAIDA = 'saida';
    case AJUSTE = 'ajuste';

    public static function toSelectArray(): array
    {
        return [
            ['value' => self::ENTRADA->value, 'label' => 'Entrada'],
            ['value' => self::SAIDA->value, 'label' => 'Saida'],
            ['value' => self::AJUSTE->value, 'label' => 'Ajuste'],
        ];
    }
}
