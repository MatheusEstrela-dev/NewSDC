<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Enums;

/**
 * Situacao do parecer tecnico (RN-10).
 */
enum SituacaoParecer: string
{
    case Favoravel = 'favoravel';
    case Contrario = 'contrario';

    public function label(): string
    {
        return match ($this) {
            self::Favoravel => 'Favorável',
            self::Contrario => 'Contrário',
        };
    }

    public function ehFavoravel(): bool
    {
        return $this === self::Favoravel;
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $caso) => ['value' => $caso->value, 'label' => $caso->label()],
            self::cases(),
        );
    }
}
