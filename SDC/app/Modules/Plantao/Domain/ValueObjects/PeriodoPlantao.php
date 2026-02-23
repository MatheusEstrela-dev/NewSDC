<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Domain\ValueObjects;

/**
 * Value Object: Período do Plantão
 */
enum PeriodoPlantao: string
{
    case DIURNO = 'DIURNO';
    case NOTURNO = 'NOTURNO';
    case EXTRAORDINARIO = 'EXTRAORDINARIO';

    public function label(): string
    {
        return match ($this) {
            self::DIURNO => '07:00hs as 19:00hs',
            self::NOTURNO => '19:00hs as 07:00hs',
            self::EXTRAORDINARIO => 'Extraordinário',
        };
    }

    public static function toSelectArray(): array
    {
        return array_map(
            fn(self $periodo) => [
                'value' => $periodo->value,
                'label' => $periodo->label(),
            ],
            self::cases()
        );
    }
}
