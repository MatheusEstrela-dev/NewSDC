<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\DTOs;

use Carbon\CarbonInterface;

/**
 * Conclusao de uma inscricao segundo o seu CriterioConclusao. Aproveitamento
 * (0 a 100) so existe quando o curso tem prova; em eventos e null.
 */
final readonly class ResultadoConclusao
{
    public function __construct(
        public CarbonInterface $concluidoEm,
        public ?float $aproveitamento,
    ) {}
}
