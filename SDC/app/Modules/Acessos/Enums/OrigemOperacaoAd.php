<?php

declare(strict_types=1);

namespace App\Modules\Acessos\Enums;

/** De onde veio o pedido da operacao no AD. */
enum OrigemOperacaoAd: string
{
    case ACESSOS = 'acessos';
    case DEMANDA = 'demanda';

    public function label(): string
    {
        return match ($this) {
            self::ACESSOS => 'Acessos',
            self::DEMANDA => 'Demanda',
        };
    }
}
