<?php

declare(strict_types=1);

namespace App\Modules\Ranking\Exceptions;

use DomainException;

/**
 * Publicacao recusada: base + bonus da regra passaria do teto por lancamento.
 *
 * O ScoreCalculator mandaria TODA entrega dessa regra para em_apuracao
 * (teto_excedido), e o administrador so descobriria pelo extrato. Recusar na
 * publicacao mantem o catalogo coerente com o que o calculo aceita.
 */
final class TetoDeLancamentoExcedido extends DomainException
{
    public static function para(int $pontosBase, int $pontosBonus, int $teto): self
    {
        return new self(
            "Base {$pontosBase} + bonus {$pontosBonus} = " . ($pontosBase + $pontosBonus)
            . " pontos passa do teto de {$teto} por lancamento."
        );
    }
}
