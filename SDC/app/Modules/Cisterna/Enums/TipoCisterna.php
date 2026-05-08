<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Enums;

enum TipoCisterna: string
{
    case COMUNITARIA = 'comunitaria';
    case INDIVIDUAL = 'individual';
    case ESCOLAR = 'escolar';

    public function label(): string
    {
        return match ($this) {
            self::COMUNITARIA => 'Comunitaria',
            self::INDIVIDUAL => 'Individual',
            self::ESCOLAR => 'Escolar',
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $case): array => ['value' => $case->value, 'label' => $case->label()],
            self::cases(),
        );
    }
}
