<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\DTOs;

use Carbon\CarbonInterface;

/**
 * Conclusao de uma inscricao segundo o seu CriterioConclusao. Aproveitamento
 * (0 a 100) so existe quando o curso tem prova; em eventos e null.
 * gravadaAgora: esta chamada foi quem congelou a conclusao (nao uma anterior
 * nem uma requisicao concorrente).
 */
final readonly class ResultadoConclusao
{
    public function __construct(
        public CarbonInterface $concluidoEm,
        public ?float $aproveitamento,
        public bool $gravadaAgora = false,
    ) {}
}
