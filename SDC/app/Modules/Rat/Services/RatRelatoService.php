<?php

declare(strict_types=1);

namespace App\Modules\Rat\Services;

use App\Modules\Rat\DTOs\RatRelatosDTO;
use App\Modules\Rat\Models\RatOcorrencia;

class RatRelatoService
{
    public function __construct(
        private readonly RatWriteService $writeService,
    ) {}

    /**
     * Sincroniza os relatos de uma ocorrência enviados pelo app mobile (v1Store).
     * Itens de recursos/envolvidos sao acrescentados, como antes: o app envia
     * so o que coletou em campo.
     */
    public function manageRelatos(RatOcorrencia $ocorrencia, RatRelatosDTO $relatos): void
    {
        $this->writeService->salvarRelatos((string) $ocorrencia->id, $relatos, substituirListas: false);
    }
}
