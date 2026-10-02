<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Enums;

enum StatusRemanejamento: string
{
    case ATIVO = 'ativo';
    case DESFEITO = 'desfeito';

    public function label(): string
    {
        return match ($this) {
            self::ATIVO => 'Ativo',
            self::DESFEITO => 'Desfeito',
        };
    }

    /** @return list<array{value:string,label:string}> */
    public static function options(): array
    {
        return array_map(static fn (self $status): array => [
            'value' => $status->value,
            'label' => $status->label(),
        ], self::cases());
    }
}
