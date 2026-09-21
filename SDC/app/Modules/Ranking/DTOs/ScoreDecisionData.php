<?php

declare(strict_types=1);

namespace App\Modules\Ranking\DTOs;

use App\Modules\Ranking\Enums\DecisaoPontuacao;
use InvalidArgumentException;

/** Resultado de credito; estornos sao processados em servico proprio. */
final readonly class ScoreDecisionData
{
    public function __construct(
        public DecisaoPontuacao $decisao,
        public int $pontosBase,
        public int $pontosBonus,
        public string $motivo,
    ) {
        if ($pontosBase < 0 || $pontosBonus < 0 || $pontosBase > PHP_INT_MAX - $pontosBonus) {
            throw new InvalidArgumentException('Pontuacao fora do intervalo inteiro suportado.');
        }

        if ($decisao === DecisaoPontuacao::Estornada || trim($motivo) === '') {
            throw new InvalidArgumentException('Decisao de credito invalida ou sem motivo.');
        }

        if (in_array($decisao, [DecisaoPontuacao::Zero, DecisaoPontuacao::EmApuracao], true)
            && ($pontosBase !== 0 || $pontosBonus !== 0)) {
            throw new InvalidArgumentException('Zero e apuracao nao podem conter pontos.');
        }
    }

    public function pontosTotais(): int
    {
        return $this->pontosBase + $this->pontosBonus;
    }

    public function pontosParaSaldo(): int
    {
        return $this->decisao->somaAoSaldo() ? $this->pontosTotais() : 0;
    }
}
