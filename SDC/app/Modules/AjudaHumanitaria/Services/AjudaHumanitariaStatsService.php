<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Services;

use App\Modules\AjudaHumanitaria\Models\Auxilio;

class AjudaHumanitariaStatsService
{
    public function getTotal(): int
    {
        return Auxilio::count();
    }

    /**
     * Retorna array [{label: 'Jan', value: 42}] para os ultimos $months meses.
     */
    public function getMonthlyCounts(int $months = 12): array
    {
        $inicio = now()->subMonths($months - 1)->startOfMonth();

        $rows = Auxilio::selectRaw('YEAR(created_at) as ano, MONTH(created_at) as mes, COUNT(*) as total')
            ->where('created_at', '>=', $inicio)
            ->groupBy('ano', 'mes')
            ->orderBy('ano')
            ->orderBy('mes')
            ->get()
            ->keyBy(fn ($r) => "{$r->ano}-{$r->mes}");

        return $this->buildMonthlyArray($months, $rows);
    }

    private function buildMonthlyArray(int $months, $rows): array
    {
        $result = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $date     = now()->subMonths($i);
            $key      = "{$date->year}-{$date->month}";
            $result[] = [
                'label' => $date->locale('pt_BR')->isoFormat('MMM'),
                'value' => (int) ($rows[$key]->total ?? 0),
            ];
        }
        return $result;
    }
}
