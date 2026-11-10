<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Enums;

enum TipoTreinamento: string
{
    case PRESENCIAL = 'PRESENCIAL';
    case ONLINE = 'ONLINE';
    case EAD = 'EAD';

    public function getLabel(): string
    {
        return match ($this) {
            self::PRESENCIAL => 'Presencial',
            self::ONLINE => 'Online',
            self::EAD => 'EAD (curso com trilha)',
        };
    }

    public function getBadgeColor(): string
    {
        return 'blue';
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::PRESENCIAL => 'users',
            self::ONLINE => 'computer-desktop',
            self::EAD => 'academic-cap',
        };
    }

    public function requerLocal(): bool
    {
        return $this === self::PRESENCIAL;
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
