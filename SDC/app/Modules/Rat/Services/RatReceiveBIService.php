<?php

declare(strict_types=1);

namespace App\Modules\Rat\Services;

use App\Modules\Rat\DTOs\RatReceiveBIDTO;
use App\Modules\Rat\Models\Rat;

/**
 * Responsabilidade unica: receber dados externos via API e persistir como Rat.
 *
 * Delega criacao para RatWriteService — nunca acessa o repositorio diretamente.
 * Se finalize=true, finaliza o RAT apos criacao.
 */
class RatReceiveBIService
{
    public function __construct(
        private readonly RatWriteService $writeService,
    ) {}

    /**
     * Cria um novo RAT a partir de dados externos.
     * Se $dto->finalize === true, finaliza o RAT apos a criacao.
     */
    public function receive(RatReceiveBIDTO $dto): Rat
    {
        $rat = $this->writeService->createWithData($dto->toModelArray());

        if ($dto->finalize) {
            $rat = $this->writeService->finalize($rat->id);
        }

        return $rat;
    }
}
