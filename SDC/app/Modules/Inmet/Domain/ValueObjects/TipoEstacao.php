<?php

declare(strict_types=1);

namespace App\Modules\Inmet\Domain\ValueObjects;

enum TipoEstacao: string
{
    case AUTOMATICA = 'automatica';
    case MANUAL = 'manual';

    public function getLabel(): string
    {
        return match ($this) {
            self::AUTOMATICA => 'Automática',
            self::MANUAL => 'Manual',
        };
    }

    public function getBadgeColor(): string
    {
        return match ($this) {
            self::AUTOMATICA => 'blue',
            self::MANUAL => 'gray',
        };
    }

    public static function toSelectArray(): array
    {
        return array_map(
            fn(self $tipo) => [
                'value' => $tipo->value,
                'label' => $tipo->getLabel(),
                'color' => $tipo->getBadgeColor(),
            ],
            self::cases()
        );
    }
}
