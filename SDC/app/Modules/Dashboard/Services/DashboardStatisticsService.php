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
use App\Modules\PlanCon\Services\PlanoContingenciaService;
use App\Modules\Plantao\Models\Viatura;
use App\Modules\Plantao\Services\ViaturaService;
use App\Support\Concurrency\Concurrency;
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

    /**
     * Janela stale (segundos): apos STATS_TTL, o valor antigo continua sendo
     * servido enquanto UMA request recomputa em background (Cache::flexible).
     * Evita stampede: sem isso, N requests concorrentes no miss recomputam
     * as ~35 queries juntas.
     */
    private const STATS_STALE_TTL = 300;

    private array $tableExistsCache = [];

    public function __construct(
        private readonly ProcessoStatsService             $processoStats,
        private readonly AjudaHumanitariaStatsService    $ahStats,
        private readonly PlanoContingenciaService        $planConStats,
    ) {}

    public function getStats(): DashboardStatsDTO
    {
        if (filter_var(env('DASHBOARD_LIGHTWEIGHT', false), FILTER_VALIDATE_BOOLEAN)) {
            return $this->getLightweightStats();
        }

        // Antes: ~12 queries sequenciais (4 counts + 4 trends x2) a cada load,
        // sem cache. Em burst de inicio de plantao o Postgres recebia N recomputos.
        // Cache::flexible (stale-while-revalidate): dentro de STATS_TTL serve
        // do cache; entre STATS_TTL e STATS_STALE_TTL serve o valor antigo e
        // apenas UMA request recomputa apos enviar a resposta (defer).
        return Cache::flexible(
            'dashboard.stats.full',
            [self::STATS_TTL, self::STATS_STALE_TTL],
            fn () => $this->computeStats(),
        );
    }

    private function computeStats(): DashboardStatsDTO
    {
        // As 4 particoes de queries sao independentes entre si e rodam em
        // paralelo nos task workers (sequencial no fallback, mesmo resultado).
        // A montagem do DTO permanece no worker HTTP. Sem cache interno nas
        // particoes: o getStats() ja cacheia o DTO inteiro.
        $partes = Concurrency::tasks([
            'countsTrends' => static fn () => app(self::class)->computeCountsAndTrends(),
            'bar6'         => static fn () => app(self::class)->buildBarData(6),
            'bar12'        => static fn () => app(self::class)->buildBarData(12),
            'sparklines'   => static fn () => app(self::class)->buildSparklines(),
        ]);

        $c = $partes['countsTrends'];

        $moduleDistribution = $this->buildDistribution(
            $c['ratAbertas'],
            $c['paeEmAnalise'],
            $c['decretosAprovados'],
            $c['demandasConcluidas'],
            $c['ahTotal'],
        );

        return new DashboardStatsDTO(
            ratAbertas:         $c['ratAbertas'],
            paeEmAnalise:       $c['paeEmAnalise'],
            decretosAprovados:  $c['decretosAprovados'],
            demandasConcluidas: $c['demandasConcluidas'],
            ratTrend:           $c['ratTrend'],
            paeTrend:           $c['paeTrend'],
            decretoTrend:       $c['decretoTrend'],
            demandaTrend:       $c['demandaTrend'],
            moduleDistribution: $moduleDistribution,
            barData6M:          $partes['bar6'],
            barData12M:         $partes['bar12'],
            sparklines:         $partes['sparklines'],
            planConStats:       $this->planConStats->getStatistics(),
            frotaStats:         $this->frotaStats(),
        );
    }

    /**
     * Particao de counts e trends (13 queries). Publico e auto-contido: e
     * resolvido via app() dentro de uma task do Concurrency::tasks(), que
     * pode executar em outro processo; retorna apenas escalares.
     *
     * @return array<string, int|float>
     */
    public function computeCountsAndTrends(): array
    {
        $processoStats = $this->tableExists(Processo::class)
            ? $this->processoStats->getStatistics()
            : ['aprovados' => 0];

        return [
            'ratAbertas'         => $this->safeCount(RatOcorrencia::class),
            'paeEmAnalise'       => $this->safeCountWhere(PaeProtocolo::class, 'status', PaeProtocoloStatus::ANALISE->value),
            'decretosAprovados'  => $processoStats['aprovados'],
            'demandasConcluidas' => $this->safeCountWhere(Task::class, 'status', 'concluida'),
            'ratTrend'           => $this->calcTrend(RatOcorrencia::class),
            'paeTrend'           => $this->calcTrendWithWhere(PaeProtocolo::class, 'status', PaeProtocoloStatus::ANALISE->value),
            'decretoTrend'       => $this->calcTrendWithLike(Processo::class, 'reconhecimento', 'Reconhecido%'),
            'demandaTrend'       => $this->calcTrendWithWhere(Task::class, 'status', 'concluida'),
            'ahTotal'            => $this->ahStats->getTotal(),
        ];
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

    /**
     * Situacao da frota para a Visao Geral.
     *
     * Delega ao ViaturaService em vez de recontar aqui: ele e quem sabe que
     * viatura DISPONIVEL com reserva agendada NAO conta como disponivel, e
     * duplicar essa regra no dashboard faria as duas telas discordarem sobre
     * quantos carros estao livres.
     *
     * tableExists porque a Visao Geral carrega em ambiente que pode nao ter o
     * modulo migrado -- mesmo cuidado dos demais blocos deste servico.
     *
     * @return array<string, int>
     */
    private function frotaStats(): array
    {
        $vazio = ['total' => 0, 'disponiveis' => 0, 'reservadas' => 0, 'em_transito' => 0, 'indisponiveis' => 0];

        if (!$this->tableExists(Viatura::class)) {
            return $vazio;
        }

        try {
            return app(ViaturaService::class)->getStatistics();
        } catch (\Throwable) {
            // A Visao Geral nao pode cair por causa de um bloco de modulo.
            return $vazio;
        }
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
            planConStats:       $this->planConStats->getStatistics(),
            frotaStats:         $this->frotaStats(),
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
