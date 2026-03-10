<?php

declare(strict_types=1);

namespace App\Modules\Rat\Services;

use App\Modules\Rat\Domain\Repositories\RatRepositoryInterface;
use Illuminate\Support\Facades\DB;

/**
 * Gera números de protocolo únicos e sequenciais para RATs.
 *
 * Formato: RAT-AAAA-NNNNN  (ex.: RAT-2026-00042)
 * Usa SELECT ... FOR UPDATE dentro de transação para garantir unicidade
 * mesmo sob acesso concorrente.
 */
class RatProtocoloService
{
    public function __construct(
        private readonly RatRepositoryInterface $repository,
    ) {}

    /**
     * Gera o próximo protocolo disponível para o ano corrente.
     * Deve ser chamado dentro de uma transação DB para garantir atomicidade.
     */
    public function generate(): string
    {
        $year     = (int) date('Y');
        $sequence = $this->repository->getLatestSequence($year) + 1;

        return sprintf('RAT-%d-%05d', $year, $sequence);
    }
}
