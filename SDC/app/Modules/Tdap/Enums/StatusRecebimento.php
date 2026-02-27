<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Enums;

enum StatusRecebimento: string
{
    case PENDENTE = 'pendente';
    case PROCESSADO = 'processado';
    case CANCELADO = 'cancelado';

    public static function toSelectArray(): array
    {
        return [
            ['value' => self::PENDENTE->value, 'label' => 'Pendente'],
            ['value' => self::PROCESSADO->value, 'label' => 'Processado'],
            ['value' => self::CANCELADO->value, 'label' => 'Cancelado'],
        ];
    }
}
