<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Services;

use App\Models\Rat\RatOcorrencia;
use App\Modules\AjudaHumanitaria\Models\Auxilio;
use App\Modules\AjudaHumanitaria\Services\AjudaHumanitariaStatsService;
use App\Modules\Dashboard\DTOs\DashboardStatsDTO;
use App\Modules\Decretacoes\Models\Processo;
use App\Modules\Decretacoes\Services\ProcessoStatsService;
use App\Modules\Demandas\Models\Task;
use App\Modules\Pae\Enums\PaeProtocoloStatus;
use App\Modules\Pae\Models\PaeProtocolo;
use App\Modules\Rat\Services\RatStatisticsService;
use Illuminate\Support\Facades\Cache;

class DashboardStatisticsService
{
    public function __construct(
        private readonly RatStatisticsService            $ratStats,
        private readonly ProcessoStatsService             $processoStats,
        private readonly AjudaHumanitariaStatsService    $ahStats,
    ) {}

    public function getStats(): DashboardStatsDTO
    {
        $ratStatistics  = $this->ratStats->getStatistics();
        $processoStats  = $this->processoStats->getStatistics();

        $ratAbertas         = $ratStatistics->total;
        $paeEmAnalise       = PaeProtocolo::where('status', PaeProtocoloStatus::ANALISE->value)->count();
        $decretosAprovados  = $processoStats['aprovados'];
        $demandasConcluidas = Task::where('status', 'concluida')->count();

        $ratTrend     = $this->calcTrend(RatOcorrencia::class);
        $paeTrend     = $this->calcTrendWithWhere(PaeProtocolo::class, 'status', PaeProtocoloStatus::ANALISE->value);
        $decretoTrend = $this->calcTrendWithLike(Processo::class, 'reconhecimento', 'Reconhecido%');
        $demandaTrend = $this->calcTrendWithWhere(Task::class, 'status', 'concluida');

        $barData6M  = Cache::remember('dashboard.bar_data_6m', 60, fn () => $this->buildBarData(6));
        $barData12M = Cache::remember('dashboard.bar_data_12m', 60, fn () => $this->buildBarData(12));
        $sparklines = Cache::remember('dashboard.sparklines', 60, fn () => $this->buildSparklines());

        $ahTotal            = $this->ahStats->getTotal();
        $moduleDistribution = $this->buildDistribution($ratAbertas, $paeEmAnalise, $decretosAprovados, $demandasConcluidas, $ahTotal);

        return new DashboardStatsDTO(
            ratAbertas:         $ratAbertas,
            paeEmAnalise:       $paeEmAnalise,
            decretosAprovados:  $decretosAprovados,
            demandasConcluidas: $demandasConcluidas,
            ratTrend:           $ratTrend,
            paeTrend:           $paeTrend,
            decretoTrend:       $decretoTrend,
            demandaTrend:       $demandaTrend,
            moduleDistribution: $moduleDistribution,
            barData6M:          $barData6M,
            barData12M:         $barData12M,
            sparklines:         $sparklines,
        );
    }

    private function calcTrend(string $modelClass): float
    {
        $thisMonth = $modelClass::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)->count();
        $prevMonth = $modelClass::whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)->count();

        if ($prevMonth === 0) {
            return $thisMonth > 0 ? 100.0 : 0.0;
        }

        return round((($thisMonth - $prevMonth) / $prevMonth) * 100, 1);
    }

    private function calcTrendWithWhere(string $modelClass, string $column, string $value): float
    {
        $thisMonth = $modelClass::where($column, $value)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)->count();
        $prevMonth = $modelClass::where($column, $value)
            ->whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)->count();

        if ($prevMonth === 0) {
            return $thisMonth > 0 ? 100.0 : 0.0;
        }

        return round((($thisMonth - $prevMonth) / $prevMonth) * 100, 1);
    }

    private function calcTrendWithLike(string $modelClass, string $column, string $value): float
    {
        $thisMonth = $modelClass::where($column, 'like', $value)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)->count();
        $prevMonth = $modelClass::where($column, 'like', $value)
            ->whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)->count();

        if ($prevMonth === 0) {
            return $thisMonth > 0 ? 100.0 : 0.0;
        }

        return round((($thisMonth - $prevMonth) / $prevMonth) * 100, 1);
    }

    private function buildBarData(int $months): array
    {
        $inicio = now()->subMonths($months - 1)->startOfMonth();

        $sources = [
            RatOcorrencia::class,
            PaeProtocolo::class,
            Task::class,
            Auxilio::class,
            Processo::class,
        ];

        $totals = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $totals["{$date->year}-{$date->month}"] = [
                'label' => $date->locale('pt_BR')->isoFormat('MMM'),
                'value' => 0,
            ];
        }

        foreach ($sources as $modelClass) {
            $rows = $modelClass::selectRaw('YEAR(created_at) as ano, MONTH(created_at) as mes, COUNT(*) as total')
                ->where('created_at', '>=', $inicio)
                ->groupBy('ano', 'mes')
                ->get();

            foreach ($rows as $row) {
                $key = "{$row->ano}-{$row->mes}";
                if (isset($totals[$key])) {
                    $totals[$key]['value'] += (int) $row->total;
                }
            }
        }

        return array_values($totals);
    }

    private function buildSparklines(): array
    {
        $modules = [
            ['name' => 'RAT',             'model' => RatOcorrencia::class, 'variant' => 'info',    'where' => null],
            ['name' => 'PAE',             'model' => PaeProtocolo::class,  'variant' => 'warning', 'where' => null],
            ['name' => 'Decretacoes',     'model' => Processo::class,      'variant' => 'success', 'where' => null],
            ['name' => 'Demandas',        'model' => Task::class,          'variant' => 'danger',  'where' => null],
            ['name' => 'Aj. Humanitaria', 'model' => Auxilio::class,       'variant' => 'primary', 'where' => null],
        ];

        return array_map(function (array $mod) {
            $months     = 7;
            $inicio     = now()->subMonths($months - 1)->startOfMonth();
            $modelClass = $mod['model'];

            $rows = $modelClass::selectRaw('YEAR(created_at) as ano, MONTH(created_at) as mes, COUNT(*) as total')
                ->where('created_at', '>=', $inicio)
                ->groupBy('ano', 'mes')
                ->get()
                ->keyBy(fn ($r) => "{$r->ano}-{$r->mes}");

            $data = [];
            for ($i = $months - 1; $i >= 0; $i--) {
                $date   = now()->subMonths($i);
                $key    = "{$date->year}-{$date->month}";
                $data[] = (int) ($rows[$key]->total ?? 0);
            }

            $current  = end($data) ?: 0;
            $previous = $data[count($data) - 2] ?? 0;
            $trend    = $previous === 0
                ? ($current > 0 ? 100.0 : 0.0)
                : round((($current - $previous) / $previous) * 100, 1);

            return [
                'name'    => $mod['name'],
                'value'   => $current,
                'trend'   => $trend,
                'variant' => $mod['variant'],
                'data'    => $data,
            ];
        }, $modules);
    }

    private function buildDistribution(int $rat, int $pae, int $decretos, int $demandas, int $ah): array
    {
        $total = $rat + $pae + $decretos + $demandas + $ah;
        $pct   = fn (int $v) => $total > 0 ? round(($v / $total) * 100, 1) : 0.0;

        return [
            ['name' => 'RAT',             'value' => $rat,      'percent' => $pct($rat),      'color' => '#06b6d4'],
            ['name' => 'PAE',             'value' => $pae,      'percent' => $pct($pae),      'color' => '#f59e0b'],
            ['name' => 'Decretacoes',     'value' => $decretos, 'percent' => $pct($decretos), 'color' => '#10b981'],
            ['name' => 'Demandas',        'value' => $demandas, 'percent' => $pct($demandas), 'color' => '#ef4444'],
            ['name' => 'Aj. Humanitaria', 'value' => $ah,       'percent' => $pct($ah),       'color' => '#8b5cf6'],
        ];
    }
}
