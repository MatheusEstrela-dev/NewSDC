<?php

declare(strict_types=1);

namespace App\Modules\Rat\Services;

use App\Modules\Rat\DTOs\RatStatisticsDTO;
use Illuminate\Support\Facades\DB;

/**
 * Responsabilidade unica: calcular estatisticas de RATs do banco real.
 * Consolidado: 4 queries -> 1 query via conditional aggregation.
 */
class RatStatisticsService
{
    /**
     * Retorna estatisticas consolidadas do banco de dados real.
     * 1 unica query com conditional aggregation.
     */
    public function getStatistics(): RatStatisticsDTO
    {
        $row = DB::table('rat_ocorrencias')
            ->selectRaw("
                COUNT(*) as total,
                COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as hoje,
                COUNT(CASE WHEN MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE()) THEN 1 END) as este_mes,
                COUNT(CASE WHEN YEAR(created_at) = YEAR(CURDATE()) THEN 1 END) as este_ano
            ")
            ->first();

        return new RatStatisticsDTO(
            total:   (int) $row->total,
            hoje:    (int) $row->hoje,
            esteMes: (int) $row->este_mes,
            esteAno: (int) $row->este_ano,
        );
    }
}

