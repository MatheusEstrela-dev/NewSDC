<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Enums;

enum ParecerVistoria: string
{
    case Aprovada = 'aprovada';
    case Reprovada = 'reprovada';

    public function label(): string
    {
        return match ($this) {
            self::Aprovada  => 'Aprovada',
            self::Reprovada => 'Reprovada',
        };
    }

    public function isAprovada(): bool
    {
        return $this === self::Aprovada;
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $c) => ['value' => $c->value, 'label' => $c->label()],
            self::cases(),
        );
    }
}
