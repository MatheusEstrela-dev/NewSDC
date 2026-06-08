<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Services;

use App\Modules\Rat\Models\RatOcorrencia;
use App\Modules\AjudaHumanitaria\Models\Auxilio;
use App\Modules\AjudaHumanitaria\Services\AjudaHumanitariaStatsService;
use App\Modules\Dashboard\DTOs\DashboardStatsDTO;
use App\Modules\Decretacoes\Models\Processo;
use App\Modules\Decretacoes\Services\ProcessoStatsService;
use App\Modules\Demandas\Models\Task;
use App\Modules\Pae\Enums\PaeProtocoloStatus;
use App\Modules\Pae\Models\PaeProtocolo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class DashboardStatisticsService
{
    /**
     * TTL do cache das estatisticas globais do dashboard, em segundos.
     * Stats globais (nao por usuario) nao precisam de frescor ao segundo;
     * cachear o DTO inteiro elimina ~12 queries sequenciais por load.
     */
    private const STATS_TTL = 60;

    private array $tableExistsCache = [];

    public function __construct(
        private readonly ProcessoStatsService             $processoStats,
        private readonly AjudaHumanitariaStatsService    $ahStats,
    ) {}

    public function getStats(): DashboardStatsDTO
    {
        if (filter_var(env('DASHBOARD_LIGHTWEIGHT', false), FILTER_VALIDATE_BOOLEAN)) {
            return $this->getLightweightStats();
        }

        // Antes: ~12 queries sequenciais (4 counts + 4 trends x2) a cada load,
        // sem cache. Em burst de inicio de plantao o Postgres recebia N recomputos.
        // Agora: 1 recomputo a cada STATS_TTL; nas demais loads, zero query.
        return Cache::remember(
            'dashboard.stats.full',
            self::STATS_TTL,
            fn () => $this->computeStats(),
        );
    }

    private function computeStats(): DashboardStatsDTO
    {
        $processoStats = $this->tableExists(Processo::class)
            ? $this->processoStats->getStatistics()
            : ['aprovados' => 0];

        $ratAbertas         = $this->safeCount(RatOcorrencia::class);
        $paeEmAnalise       = $this->safeCountWhere(PaeProtocolo::class, 'status', PaeProtocoloStatus::ANALISE->value);
        $decretosAprovados  = $processoStats['aprovados'];
        $demandasConcluidas = $this->safeCountWhere(Task::class, 'status', 'concluida');

        $ratTrend     = $this->calcTrend(RatOcorrencia::class);
        $paeTrend     = $this->calcTrendWithWhere(PaeProtocolo::class, 'status', PaeProtocoloStatus::ANALISE->value);
        $decretoTrend = $this->calcTrendWithLike(Processo::class, 'reconhecimento', 'Reconhecido%');
        $demandaTrend = $this->calcTrendWithWhere(Task::class, 'status', 'concluida');

        // Sem cache interno aqui: o getStats() ja cacheia o DTO inteiro.
        $barData6M  = $this->buildBarData(6);
        $barData12M = $this->buildBarData(12);
        $sparklines = $this->buildSparklines();

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
        if (!$this->tableExists($modelClass)) {
            return 0.0;
        }

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
        if (!$this->tableExists($modelClass)) {
            return 0.0;
        }

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
        if (!$this->tableExists($modelClass)) {
            return 0.0;
        }

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

        $anoExpr = \App\Support\Database\PgCompat::extractDatePart('YEAR', 'created_at');
        $mesExpr = \App\Support\Database\PgCompat::extractDatePart('MONTH', 'created_at');

        foreach ($sources as $modelClass) {
            if (!$this->tableExists($modelClass)) {
                continue;
            }

            $rows = $modelClass::selectRaw("{$anoExpr} as ano, {$mesExpr} as mes, COUNT(*) as total")
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

            if (!$this->tableExists($modelClass)) {
                return [
                    'name'    => $mod['name'],
                    'value'   => 0,
                    'trend'   => 0.0,
                    'variant' => $mod['variant'],
                    'data'    => array_fill(0, $months, 0),
                ];
            }

            $rows = $modelClass::selectRaw('EXTRACT(YEAR FROM created_at)::int as ano, EXTRACT(MONTH FROM created_at)::int as mes, COUNT(*) as total')
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

    private function safeCount(string $modelClass): int
    {
        return $this->tableExists($modelClass) ? $modelClass::count() : 0;
    }

    private function safeCountWhere(string $modelClass, string $column, string $value): int
    {
        return $this->tableExists($modelClass)
            ? $modelClass::where($column, $value)->count()
            : 0;
    }

    private function safeCountLike(string $modelClass, string $column, string $value): int
    {
        return $this->tableExists($modelClass)
            ? $modelClass::where($column, 'like', $value)->count()
            : 0;
    }

    private function tableExists(string $modelClass): bool
    {
        $table = (new $modelClass())->getTable();

        return $this->tableExistsCache[$table] ??= Schema::hasTable($table);
    }

    private function getLightweightStats(): DashboardStatsDTO
    {
        $ratAbertas         = $this->safeCount(RatOcorrencia::class);
        $paeEmAnalise       = $this->safeCountWhere(PaeProtocolo::class, 'status', PaeProtocoloStatus::ANALISE->value);
        $decretosAprovados  = $this->safeCountLike(Processo::class, 'reconhecimento', 'Reconhecido%');
        $demandasConcluidas = $this->safeCountWhere(Task::class, 'status', 'concluida');
        $ahTotal            = $this->safeCount(Auxilio::class);

        return new DashboardStatsDTO(
            ratAbertas:         $ratAbertas,
            paeEmAnalise:       $paeEmAnalise,
            decretosAprovados:  $decretosAprovados,
            demandasConcluidas: $demandasConcluidas,
            ratTrend:           0.0,
            paeTrend:           0.0,
            decretoTrend:       0.0,
            demandaTrend:       0.0,
            moduleDistribution: $this->buildDistribution($ratAbertas, $paeEmAnalise, $decretosAprovados, $demandasConcluidas, $ahTotal),
            barData6M:          $this->emptyMonthlyData(6),
            barData12M:         $this->emptyMonthlyData(12),
            sparklines:         [],
        );
    }

    private function emptyMonthlyData(int $months): array
    {
        $data = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $data[] = [
                'label' => $date->locale('pt_BR')->isoFormat('MMM'),
                'value' => 0,
            ];
        }

        return $data;
    }
}
