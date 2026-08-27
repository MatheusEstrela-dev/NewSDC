<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Enums;

enum NivelCombustivel: string
{
    case VAZIO = 'VAZIO';
    case QUARTO_1 = 'QUARTO_1';
    case QUARTO_2 = 'QUARTO_2';
    case QUARTO_3 = 'QUARTO_3';
    case QUARTO_4 = 'QUARTO_4';

    public function label(): string
    {
        return match ($this) {
            self::VAZIO => '0/4',
            self::QUARTO_1 => '1/4',
            self::QUARTO_2 => '2/4',
            self::QUARTO_3 => '3/4',
            self::QUARTO_4 => '4/4',
        };
    }

    /**
     * Percentual do tanque. Consumido pelo gauge do frontend; o enum devolve
     * numero, nunca classe CSS (Tailwind nao escaneia app/**\/*.php).
     */
    public function percentual(): int
    {
        return match ($this) {
            self::VAZIO => 0,
            self::QUARTO_1 => 25,
            self::QUARTO_2 => 50,
            self::QUARTO_3 => 75,
            self::QUARTO_4 => 100,
        };
    }

    public static function toSelectArray(): array
    {
        return array_map(
            fn(self $nivel) => [
                'value' => $nivel->value,
                'label' => $nivel->label(),
            ],
            self::cases()
        );
    }
}
