<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes\Enums;

enum MockMunicipio: int
{
    case BELO_HORIZONTE = 1;
    case CONTAGEM = 2;
    case UBERLANDIA = 3;
    case JUIZ_DE_FORA = 4;
    case BETIM = 5;

    public function label(): string
    {
        return match ($this) {
            self::BELO_HORIZONTE => 'Belo Horizonte',
            self::CONTAGEM => 'Contagem',
            self::UBERLANDIA => 'Uberlandia',
            self::JUIZ_DE_FORA => 'Juiz de Fora',
            self::BETIM => 'Betim',
        };
    }

    public static function toSelectOptions(): array
    {
        return array_map(
            fn (self $case) => ['id' => $case->value, 'p_nome' => $case->label(), 'label' => $case->label(), 'total' => rand(1, 10)],
            self::cases()
        );
    }
}
