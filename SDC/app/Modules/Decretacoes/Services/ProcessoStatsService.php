<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes\Services;

use App\Modules\Decretacoes\Filters\ProcessoFilter;
use App\Modules\Decretacoes\Models\DecretoMunicipio;
use App\Modules\Decretacoes\Models\Processo;
use App\Support\Concurrency\Concurrency;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;

/**
 * Service responsible for Processo statistics and dashboard metrics.
 * Cache: 30 minutos para estatisticas de dashboard (quando sem filtros).
 *
 * Calcula estatisticas para os 5 cards do dashboard de Decretacoes:
 * - Total de Eventos
 * - Registros
 * - Decretacoes
 * - Municipios Atingidos
 * - Decretacoes Vigentes
 *
 * Cada card tem breakdown por tipo de desastre (ECP/SE).
 */
class ProcessoStatsService
{
    private const CACHE_PREFIX = 'decretacoes.stats.';

    /**
     * Get dashboard statistics for DecretacoesStatsCards component.
     * Cached for 30 minutes if no active filters.
     *
     * Retorna estrutura compativel com o componente Vue. Cada metrica tem
     * breakdown nas tres classes de situacao_anormalidade (ECP/SE/N1):
     * - totalEventos, totalEventosEcp, totalEventosSe, totalEventosN1
     * - registros, registrosEcp, registrosSe, registrosN1
     * - decretacoes, decretacoesEcp, decretacoesSe, decretacoesN1
     * - municipiosAtingidos, municipiosAtingidosEcp, municipiosAtingidosSe, municipiosAtingidosN1
     * - decretacoesVigentes, decretacoesVigentesEcp, decretacoesVigentesSe, decretacoesVigentesN1
     *
     * @param array $filters Filtros opcionais aplicados na interface
     * @return array<string, int>
     */
    public function getDashboardStatistics(array $filters = []): array
    {
        // Cada familia de card (4 counts) vira uma task independente: paralelas
        // nos task workers do Swoole, sequenciais no fallback (mesmo resultado).
        $calculaEstatisticas = function () use ($filters) {
            $familias = [
                'totalEventos',
                'registros',
                'decretacoes',
                'municipiosAtingidos',
                'decretacoesVigentes',
            ];

            $resultados = Concurrency::tasks(array_combine(
                $familias,
                array_map(
                    static fn (string $familia) => static fn () => app(ProcessoStatsService::class)
                        ->computeFamilia($familia, $filters),
                    $familias,
                ),
            ));

            return array_merge(...array_values($resultados));
        };

        // Verifica se existem filtros "ativos" (ignorando campos vazios/nulos)
        $hasFilters = collect($filters)
            ->only([
                'search', 'data_entrada', 'data_entrada_inicio', 'data_entrada_fim',
                'processo', 'reconhecimento', 'analista', 'situacao_anormalidade',
                'data_decreto_inicio', 'data_decreto_fim', 'vigencia_status',
                'tipo_desastre_id', 'municipio_id', 'n_protocolo_fide'
            ])
            ->filter(fn ($v) => $v !== null && $v !== '')
            ->isNotEmpty();

        // Sem filtros ativos: cacheia por 30 minutos. TTL (em vez de rememberForever)
        // garante auto-recuperacao apos cargas que nao passam pelo Eloquent/Observer
        // (ex.: import SQL bruto), que de outra forma deixariam a estatistica presa.
        // O sufixo '.v2' aposenta a chave legada 'dashboard' (gravada sem expiracao).
        if (!$hasFilters) {
            return Cache::remember(self::CACHE_PREFIX . 'dashboard.v2', now()->addMinutes(30), $calculaEstatisticas);
        }

        // Se houver filtros, recalcula on the fly sem tocar no cache
        return $calculaEstatisticas();
    }

    /**
     * Calcula uma familia de estatisticas (total + ECP/SE/N1) a partir dos
     * filtros crus. Publico e auto-contido de proposito: e o metodo que as
     * tasks do Concurrency::tasks() resolvem em outro processo, entao recebe
     * apenas escalares/arrays e reconstroi a query localmente.
     *
     * @param array $filters Mesmos filtros crus aceitos por getDashboardStatistics
     * @return array<string, int>
     */
    public function computeFamilia(string $familia, array $filters = []): array
    {
        $metodo = match ($familia) {
            'totalEventos'        => 'getTotalEventos',
            'registros'           => 'getRegistros',
            'decretacoes'         => 'getDecretacoes',
            'municipiosAtingidos' => 'getMunicipiosAtingidos',
            'decretacoesVigentes' => 'getDecretacoesVigentes',
            default => throw new InvalidArgumentException("Familia de estatistica desconhecida: {$familia}"),
        };

        $baseQuery = $this->buildBaseQuery($filters);

        return [
            $familia          => $this->{$metodo}($baseQuery),
            $familia . 'Ecp'  => $this->{$metodo}($baseQuery, 'ECP'),
            $familia . 'Se'   => $this->{$metodo}($baseQuery, 'SE'),
            $familia . 'N1'   => $this->{$metodo}($baseQuery, 'N1'),
        ];
    }

    /**
     * Query base de Processo com os filtros da interface aplicados (padrao
     * ProcessoFilter sobre um Request reconstruido dos filtros crus).
     */
    private function buildBaseQuery(array $filters): Builder
    {
        $baseQuery = Processo::query();

        if (!empty($filters)) {
            $request = new Request($filters);
            $filter = new ProcessoFilter($request);
            $baseQuery = $filter->apply($baseQuery);
        }

        return $baseQuery;
    }

    /**
     * Get basic statistics (legacy method).
     *
     * @return array<string, int>
     */
    public function getStatistics(): array
    {
        return [
            'total'      => Processo::count(),
            'em_analise' => Processo::where('reconhecimento', 'Registro')->count(),
            'aprovados'  => Processo::where('reconhecimento', 'like', 'Reconhecido%')->count(),
            'rejeitados' => Processo::where('reconhecimento', 'Nao reconhecido')->count(),
        ];
    }

    /**
     * Total de Eventos: conta todos os processos.
     */
    private function getTotalEventos(Builder $baseQuery, ?string $tipoDesastre = null): int
    {
        $query = clone $baseQuery;

        if ($tipoDesastre) {
            $query->where('tipo_desastre', $tipoDesastre);
        }

        return $query->count();
    }

    /**
     * Registros: conta processos com reconhecimento = 'Registro'.
     */
    private function getRegistros(Builder $baseQuery, ?string $tipoDesastre = null): int
    {
        $query = clone $baseQuery;
        $query->where('reconhecimento', 'Registro');

        if ($tipoDesastre) {
            $query->where('tipo_desastre', $tipoDesastre);
        }

        return $query->count();
    }

    /**
     * Decretacoes: conta processos com reconhecimento != 'Registro'.
     */
    private function getDecretacoes(Builder $baseQuery, ?string $tipoDesastre = null): int
    {
        $query = clone $baseQuery;
        $query->where('reconhecimento', '!=', 'Registro');

        if ($tipoDesastre) {
            $query->where('tipo_desastre', $tipoDesastre);
        }

        return $query->count();
    }

    /**
     * Municipios Atingidos: conta municipios distintos com decretacoes.
     */
    private function getMunicipiosAtingidos(Builder $baseQuery, ?string $tipoDesastre = null): int
    {
        $query = clone $baseQuery;
        $query->where('reconhecimento', '!=', 'Registro');

        if ($tipoDesastre) {
            $query->where('tipo_desastre', $tipoDesastre);
        }

        $processoIds = $query->pluck('id');

        return DecretoMunicipio::whereIn('entrada_processos_id', $processoIds)
            ->distinct('municipio_id')
            ->count('municipio_id');
    }

    /**
     * Decretacoes Vigentes: conta processos vigentes reconhecidos pelo estado.
     *
     * Vigente = data_publicacao_mg NULL ou data_publicacao_mg + prazo_vigencia >= hoje
     * + reconhecimento LIKE 'Reconhecido pelo Estado%'
     */
    private function getDecretacoesVigentes(Builder $baseQuery, ?string $tipoDesastre = null): int
    {
        $query = clone $baseQuery;

        // Vigencia: data_publicacao_mg NULL ou dentro do prazo
        $query->where(function ($q) {
            $q->whereNull('data_publicacao_mg')
              ->orWhereRaw("(data_publicacao_mg + (prazo_vigencia || ' days')::interval) >= CURRENT_DATE");
        });

        // Reconhecido pelo estado
        $query->where('reconhecimento', '!=', 'Registro')
              ->where('reconhecimento', 'like', 'Reconhecido pelo Estado%');

        if ($tipoDesastre) {
            $query->where('tipo_desastre', $tipoDesastre);
        }

        return $query->count();
    }

    /**
     * Get the count of valid (vigentes) processos.
     *
     * @return int
     */
    public function getVigentesCount(): int
    {
        return Cache::rememberForever(self::CACHE_PREFIX . 'vigentes', function () {
            return Processo::where(function ($q) {
                $q->whereNull('data_publicacao_mg')
                  ->orWhereRaw("(data_publicacao_mg + (prazo_vigencia || ' days')::interval) >= CURRENT_DATE");
            })->count();
        });
    }

    /** Limpa todo o cache de estatisticas de Decretacoes (inclui a chave legada). */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_PREFIX . 'dashboard.v2');
        Cache::forget(self::CACHE_PREFIX . 'dashboard');
        Cache::forget(self::CACHE_PREFIX . 'vigentes');
    }
}
