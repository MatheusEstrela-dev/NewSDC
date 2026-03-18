<?php

declare(strict_types=1);

namespace App\Modules\Rat\Services;

use App\Models\Rat\RatOcorrencia;
use App\Modules\Rat\DTOs\RatStatisticsDTO;

/**
 * Responsabilidade unica: calcular estatisticas de RATs do banco real.
 * Usa tabela rat_ocorrencias (polimorficas).
 */
class RatStatisticsService
{
    /**
     * Retorna estatisticas consolidadas do banco de dados real.
     */
    public function getStatistics(): RatStatisticsDTO
    {
        return new RatStatisticsDTO(
            total:     $this->countTotal(),
            hoje:      $this->countToday(),
            esteMes:   $this->countThisMonth(),
            esteAno:   $this->countThisYear(),
        );
    }

    private function countTotal(): int
    {
        return RatOcorrencia::count();
    }

    private function countToday(): int
    {
        return RatOcorrencia::whereDate('created_at', today())->count();
    }

    private function countThisMonth(): int
    {
        return RatOcorrencia::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
    }

    private function countThisYear(): int
    {
        return RatOcorrencia::whereYear('created_at', now()->year)->count();
    }
}

