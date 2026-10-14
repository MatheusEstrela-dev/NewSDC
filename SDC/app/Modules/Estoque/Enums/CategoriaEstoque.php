<?php

declare(strict_types=1);

namespace App\Modules\Estoque\Enums;

/** Agrupamento da listagem do estoque (#33): Alimentos, Higiene, Dormitorio... */
enum CategoriaEstoque: string
{
    case Alimentos  = 'alimentos';
    case Higiene    = 'higiene';
    case Dormitorio = 'dormitorio';
    case Limpeza    = 'limpeza';
    case Saude      = 'saude';
    case Vestuario  = 'vestuario';
    case Outros     = 'outros';

    public function label(): string
    {
        return match ($this) {
            self::Alimentos  => 'Alimentos',
            self::Higiene    => 'Higiene',
            self::Dormitorio => 'Dormitório',
            self::Limpeza    => 'Limpeza',
            self::Saude      => 'Saúde',
            self::Vestuario  => 'Vestuário',
            self::Outros     => 'Outros',
        };
    }

    /** @return list<array{value: string, label: string}> */
    public static function options(): array
    {
        return array_map(fn (self $c) => ['value' => $c->value, 'label' => $c->label()], self::cases());
    }
}
