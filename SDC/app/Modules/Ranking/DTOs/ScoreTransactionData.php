<?php

declare(strict_types=1);

namespace App\Modules\Ranking\DTOs;

use DateTimeImmutable;

/** Fatos comprovados pelo adaptador, sem consulta ao estado atual do usuario. */
final readonly class ScoreTransactionData
{
    public function __construct(
        public DateTimeImmutable $competencia,
        public bool $autoriaComprovada,
        public bool $evidenciaComprovada,
        public bool $elegivel,
        public bool $validada,
        public ?DateTimeImmutable $entregueEm = null,
        public ?DateTimeImmutable $prazoEfetivo = null,
    ) {}
}
