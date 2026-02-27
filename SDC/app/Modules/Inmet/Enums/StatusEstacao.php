<?php

declare(strict_types=1);

namespace App\Modules\Inmet\Enums;

enum StatusEstacao: string
{
    case ATIVO = 'ativo';
    case INATIVO = 'inativo';
    case MANUTENCAO = 'manutencao';

    public function getLabel(): string
    {
        return match ($this) {
            self::ATIVO => 'Ativo',
            self::INATIVO => 'Inativo',
            self::MANUTENCAO => 'Manutencao',
        };
    }

    public function getBadgeColor(): string
    {
        return match ($this) {
            self::ATIVO => 'green',
            self::INATIVO => 'red',
            self::MANUTENCAO => 'yellow',
        };
    }

    public static function toSelectArray(): array
    {
        return array_map(
            fn(self $status) => [
                'value' => $status->value,
                'label' => $status->getLabel(),
                'color' => $status->getBadgeColor(),
            ],
            self::cases()
        );
    }
}
