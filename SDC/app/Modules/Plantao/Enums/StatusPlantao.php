<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Enums;

enum StatusPlantao: string
{
    case ATIVO = 'ATIVO';
    case FINALIZADO = 'FINALIZADO';

    public function label(): string
    {
        return match ($this) {
            self::ATIVO => 'Ativo',
            self::FINALIZADO => 'Finalizado',
        };
    }

    public static function toSelectArray(): array
    {
        return array_map(
            fn(self $status) => [
                'value' => $status->value,
                'label' => $status->label(),
            ],
            self::cases()
        );
    }
}
