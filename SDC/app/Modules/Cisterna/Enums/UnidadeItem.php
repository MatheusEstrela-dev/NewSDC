<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Enums;

enum UnidadeItem: string
{
    case UN = 'un';
    case M = 'm';

    public function label(): string
    {
        return match ($this) {
            self::UN => 'Unidade',
            self::M => 'Metro',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function valores(): array
    {
        return array_map(fn (self $c): string => $c->value, self::cases());
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $c): array => ['value' => $c->value, 'label' => $c->label()],
            self::cases(),
        );
    }
}
