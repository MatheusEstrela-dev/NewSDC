<?php

declare(strict_types=1);

namespace App\Modules\Integracao\Aplicadores\Rat;

use App\Modules\Rat\Models\RatOcorrencia;

/** Resultado exposto no status das mensagens de RAT. */
final class ResultadoDaOcorrencia
{
    /** @return array{id: string, numero_bos: ?string, status: int} */
    public static function de(RatOcorrencia $ocorrencia): array
    {
        return [
            'id'         => (string) $ocorrencia->getKey(),
            'numero_bos' => $ocorrencia->getAttribute('numero_bos'),
            'status'     => (int) $ocorrencia->getAttribute('status'),
        ];
    }
}
