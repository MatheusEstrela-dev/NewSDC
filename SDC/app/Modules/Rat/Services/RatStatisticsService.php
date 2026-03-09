<?php

declare(strict_types=1);

namespace App\Modules\Rat\Services;

use App\Modules\Rat\DTOs\RatStatisticsDTO;
use App\Modules\Rat\Models\Rat;

/**
 * Responsabilidade única: calcular estatísticas de RATs do banco real.
 *
 * SOLID - SRP: só calcula contagens, sem filtros ou CRUD.
 */
class RatStatisticsService
{
    /**
     * Retorna estatísticas consolidadas do banco de dados real.
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
        return Rat::count();
    }

    private function countToday(): int
    {
        return Rat::whereDate('created_at', today())->count();
    }

    private function countThisMonth(): int
    {
        return Rat::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
    }

    private function countThisYear(): int
    {
        return Rat::whereYear('created_at', now()->year)->count();
    }
}

