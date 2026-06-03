<?php

declare(strict_types=1);

namespace App\Modules\Compdec\Enums;

enum TipoOrgao: string
{
    case CEDEC = 'cedec';
    case REDEC = 'redec';
    case COMPDEC = 'compdec';

    public function label(): string
    {
        return match ($this) {
            self::CEDEC => 'CEDEC - Coordenadoria Estadual',
            self::REDEC => 'REDEC - Regional',
            self::COMPDEC => 'COMPDEC - Coordenadoria Municipal',
        };
    }

    public function nivelHierarquico(): int
    {
        return match ($this) {
            self::CEDEC => 0,
            self::REDEC => 1,
            self::COMPDEC => 2,
        };
    }

    public function podeSerSuperiorDe(self $tipo): bool
    {
        return match ($this) {
            self::CEDEC => $tipo === self::REDEC,
            self::REDEC => $tipo === self::COMPDEC,
            self::COMPDEC => false,
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
