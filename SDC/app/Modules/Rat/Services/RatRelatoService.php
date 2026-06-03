<?php

declare(strict_types=1);

namespace App\Modules\Rat\Services;

use App\Modules\Rat\DTOs\RatDadosGeraisDTO;
use App\Modules\Rat\DTOs\RatEnvolvidoDTO;
use App\Modules\Rat\DTOs\RatRecursoDTO;
use App\Modules\Rat\DTOs\RatVistoriaDTO;
use App\Modules\Rat\Models\RatOcorrencia;

class RatRelatoService
{
    public function __construct(
        private readonly RatWriteService $writeService,
    ) {}

    /**
     * Sincroniza todos os relatos de uma ocorrência a partir de um array genérico.
     * Usado pelo endpoint mobile v1Store.
     */
    public function manageRelatos(RatOcorrencia $ocorrencia, array $relatos): void
    {
        $id = (string) $ocorrencia->id;

        if (!empty($relatos['dadosGerais'])) {
            $this->writeService->saveDadosGerais($id, RatDadosGeraisDTO::fromArray($relatos));
        }

        if (!empty($relatos['envolvidos']) && is_array($relatos['envolvidos'])) {
            foreach ($relatos['envolvidos'] as $e) {
                $this->writeService->saveEnvolvido($id, RatEnvolvidoDTO::fromArray($e));
            }
        }

        if (!empty($relatos['recursos']) && is_array($relatos['recursos'])) {
            foreach ($relatos['recursos'] as $r) {
                $this->writeService->saveRecurso($id, RatRecursoDTO::fromArray($r));
            }
        }

        if (!empty($relatos['vistoria'])) {
            $this->writeService->saveVistoria($id, RatVistoriaDTO::fromArray($relatos['vistoria']));
        }
    }
}
