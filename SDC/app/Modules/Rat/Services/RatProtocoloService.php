<?php

declare(strict_types=1);

namespace App\Modules\Rat\Services;

use App\Modules\Rat\Models\Rat;

/**
 * Gera números de protocolo únicos e sequenciais para RATs.
 *
 * Formato: RAT-AAAA-NNNNN  (ex.: RAT-2026-00042)
 * Usa SELECT ... FOR UPDATE dentro de transação para garantir unicidade
 * mesmo sob acesso concorrente.
 */
class RatProtocoloService
{
    /**
     * Gera o próximo protocolo disponível para o ano corrente.
     * Deve ser chamado dentro de uma transação DB para garantir atomicidade.
     */
    public function generate(): string
    {
        $year     = (int) date('Y');
        $sequence = $this->getLatestSequence($year) + 1;

        return sprintf('RAT-%d-%05d', $year, $sequence);
    }

    private function getLatestSequence(int $year): int
    {
        $latest = Rat::where('protocolo', 'like', "RAT-{$year}-%")
            ->lockForUpdate()
            ->orderByDesc('protocolo')
            ->value('protocolo');

        if (!$latest) {
            return 0;
        }

        return (int) substr($latest, strrpos($latest, '-') + 1);
    }
}
