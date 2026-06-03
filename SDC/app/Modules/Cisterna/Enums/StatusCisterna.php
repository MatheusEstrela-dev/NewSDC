<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Enums;

enum StatusCisterna: string
{
    case ATIVA = 'ativa';
    case PENDENTE = 'pendente';
    case INATIVA = 'inativa';
    case EM_OBRAS = 'em_obras';

    public function label(): string
    {
        return match ($this) {
            self::ATIVA => 'Ativa',
            self::PENDENTE => 'Pendente',
            self::INATIVA => 'Inativa',
            self::EM_OBRAS => 'Em Obras',
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
