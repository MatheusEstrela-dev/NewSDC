<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes\Enums;

enum MockAnalista: string
{
    case ANALISTA_A = 'João Silva';
    case ANALISTA_B = 'Maria Gonçalves';
    case ANALISTA_C = 'Pedro Álvares';
    case ANALISTA_D = 'Ana Beatriz';

    public static function toSelectOptions(): array
    {
        return array_map(
            fn (self $case) => $case->value,
            self::cases()
        );
    }
}
