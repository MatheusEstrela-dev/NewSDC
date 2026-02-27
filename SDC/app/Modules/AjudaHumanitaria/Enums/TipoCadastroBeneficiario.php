<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Enums;

enum TipoCadastroBeneficiario: string
{
    case INDIVIDUAL = 'individual';
    case FAMILIAR = 'familiar';
    case COLETIVO = 'coletivo';

    public static function toSelectArray(): array
    {
        return [
            ['value' => self::INDIVIDUAL->value, 'label' => 'Individual'],
            ['value' => self::FAMILIAR->value, 'label' => 'Familiar'],
            ['value' => self::COLETIVO->value, 'label' => 'Coletivo'],
        ];
    }
}
