<?php

declare(strict_types=1);

namespace App\Modules\Compdec\Enums;

enum TipoAnexo: string
{
    case LEI = 'lei';
    case DECRETO = 'decreto';
    case PORTARIA = 'portaria';
    case REGIMENTO = 'regimento';
    case OUTROS = 'outros';

    public function label(): string
    {
        return match ($this) {
            self::LEI => 'Lei',
            self::DECRETO => 'Decreto',
            self::PORTARIA => 'Portaria',
            self::REGIMENTO => 'Regimento Interno',
            self::OUTROS => 'Outros',
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
