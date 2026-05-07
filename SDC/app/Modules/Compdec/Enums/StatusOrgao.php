<?php

declare(strict_types=1);

namespace App\Modules\Compdec\Enums;

enum StatusOrgao: string
{
    case ATIVO = 'ativo';
    case INATIVO = 'inativo';
    case EM_IMPLANTACAO = 'em_implantacao';
    case SUSPENSO = 'suspenso';

    public function label(): string
    {
        return match ($this) {
            self::ATIVO => 'Ativo',
            self::INATIVO => 'Inativo',
            self::EM_IMPLANTACAO => 'Em Implantacao',
            self::SUSPENSO => 'Suspenso',
        };
    }

    public function isOperacional(): bool
    {
        return $this === self::ATIVO;
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
