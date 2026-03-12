<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes\Enums;

enum MockRedec: int
{
    case REDEC_1 = 1;
    case REDEC_2 = 2;
    case REDEC_3 = 3;
    case REDEC_4 = 4;
    case REDEC_5 = 5;

    public function label(): string
    {
        return match ($this) {
            self::REDEC_1 => '1ª REDEC - RMBH',
            self::REDEC_2 => '2ª REDEC - Contagem',
            self::REDEC_3 => '3ª REDEC - Juiz de Fora',
            self::REDEC_4 => '4ª REDEC - Uberaba',
            self::REDEC_5 => '5ª REDEC - Governador Valadares',
        };
    }

    public static function toSelectOptions(): array
    {
        return array_map(
            fn (self $case) => ['id' => $case->value, 'label' => $case->label()],
            self::cases()
        );
    }
}
