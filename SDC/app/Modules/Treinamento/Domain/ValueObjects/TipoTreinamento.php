<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Domain\ValueObjects;

enum TipoTreinamento: string
{
    case PRESENCIAL = 'PRESENCIAL';
    case EAD = 'EAD';
    case HIBRIDO = 'HIBRIDO';

    public function getLabel(): string
    {
        return match ($this) {
            self::PRESENCIAL => 'Presencial',
            self::EAD => 'EAD (Ensino a Distância)',
            self::HIBRIDO => 'Híbrido',
        };
    }

    public function getBadgeColor(): string
    {
        return match ($this) {
            self::PRESENCIAL => 'blue',
            self::EAD => 'purple',
            self::HIBRIDO => 'indigo',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::PRESENCIAL => 'users',
            self::EAD => 'computer-desktop',
            self::HIBRIDO => 'globe-alt',
        };
    }

    public function requerLocal(): bool
    {
        return $this === self::PRESENCIAL || $this === self::HIBRIDO;
    }

    public static function toSelectArray(): array
    {
        return array_map(
            fn(self $tipo) => [
                'value' => $tipo->value,
                'label' => $tipo->getLabel(),
                'color' => $tipo->getBadgeColor(),
                'icon' => $tipo->getIcon(),
            ],
            self::cases()
        );
    }
}
