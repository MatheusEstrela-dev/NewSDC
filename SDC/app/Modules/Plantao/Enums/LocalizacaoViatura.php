<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Enums;

enum LocalizacaoViatura: string
{
    case PREDIO_ALTEROSAS = 'PREDIO_ALTEROSAS';
    case OFICINA = 'OFICINA';
    case CEDIDA = 'CEDIDA';
    case OUTRO = 'OUTRO';

    public function label(): string
    {
        return match ($this) {
            self::PREDIO_ALTEROSAS => 'Predio Alterosas',
            self::OFICINA => 'Oficina',
            self::CEDIDA => 'Cedida',
            self::OUTRO => 'Outro',
        };
    }

    public static function toSelectArray(): array
    {
        return array_map(
            fn(self $local) => [
                'value' => $local->value,
                'label' => $local->label(),
            ],
            self::cases()
        );
    }
}
