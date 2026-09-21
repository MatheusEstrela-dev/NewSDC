<?php

declare(strict_types=1);

namespace App\Modules\Ranking\DTOs;

use DateTimeImmutable;
use InvalidArgumentException;

/** Versao da regra selecionada pelo chamador; fim da vigencia e exclusivo. */
final readonly class ScoreRuleData
{
    public function __construct(
        public int $pontosBase,
        public bool $habilitada = true,
        public bool $aceitaBonus = true,
        public bool $exigeValidacao = true,
        public ?DateTimeImmutable $vigenteDesde = null,
        public ?DateTimeImmutable $vigenteAte = null,
    ) {
        if ($pontosBase < 0) {
            throw new InvalidArgumentException('A pontuacao base nao pode ser negativa.');
        }

        if ($vigenteDesde !== null && $vigenteAte !== null && $vigenteAte <= $vigenteDesde) {
            throw new InvalidArgumentException('O fim da vigencia deve ser posterior ao inicio.');
        }
    }

    public function vigenteNa(DateTimeImmutable $competencia): bool
    {
        return ($this->vigenteDesde === null || $competencia >= $this->vigenteDesde)
            && ($this->vigenteAte === null || $competencia < $this->vigenteAte);
    }
}
