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

        // Percentual de bonus DESTA versao da regra. Nulo cai no padrao do
        // calculador (config), para regra montada fora do catalogo. Sem este
        // campo o percentual ajustado no modal era ignorado e o placar
        // creditava o global de 20%, divergindo do que a tela prometia.
        public ?int $bonusPercentual = null,
    ) {
        if ($pontosBase < 0) {
            throw new InvalidArgumentException('A pontuacao base nao pode ser negativa.');
        }

        if ($bonusPercentual !== null && ($bonusPercentual < 0 || $bonusPercentual > 100)) {
            throw new InvalidArgumentException('O percentual de bonus deve estar entre 0 e 100.');
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
