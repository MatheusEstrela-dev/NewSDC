<?php

declare(strict_types=1);

namespace App\Modules\Resgate\Enums;

/** Tipo de item do catalogo (plano, secao 3.1). */
enum TipoItem: string
{
    case Servico = 'servico';
    case Adesao = 'adesao';
    case BemConsumo = 'bem_consumo';
    case BemPermanente = 'bem_permanente';

    public function label(): string
    {
        return match ($this) {
            self::Servico       => 'Serviço',
            self::Adesao        => 'Adesão',
            self::BemConsumo    => 'Bem de consumo',
            self::BemPermanente => 'Bem permanente',
        };
    }

    /** Bem permanente e individualizado: a disponibilidade sao as unidades. */
    public function individualizado(): bool
    {
        return $this === self::BemPermanente;
    }
}
