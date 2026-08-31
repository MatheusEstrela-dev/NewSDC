<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Enums;

enum StatusMovimentacao: string
{
    case EM_TRANSITO = 'EM_TRANSITO';
    case RETORNADA = 'RETORNADA';

    public function label(): string
    {
        return match ($this) {
            self::EM_TRANSITO => 'Em transito',
            self::RETORNADA => 'Retornada',
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
