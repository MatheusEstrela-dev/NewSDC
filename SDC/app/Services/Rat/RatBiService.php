<?php

declare(strict_types=1);

namespace App\Services\Rat;

use App\Models\Rat\RatOcorrencia;
use App\Modules\Rat\Models\Relatos\RatRelatoEnvolvidos;
use App\Modules\Rat\Models\Relatos\RatRelatoRecurso;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Agrega dados das ocorrências RAT para consumo em dashboards BI.
 * Cache: 1 hora para estatisticas agregadas.
 *
 * Métodos públicos:
 *   getOcorrenciasPorStatus()  — contagem agrupada por status
 *   getOcorrenciasPorMes()     — contagem mensal do ano corrente
 *   getEnvolvidosPorTipo()     — distribuição por tipo de pessoa
 *   getRecursosPorTipo()       — distribuição por tipo de recurso
 *   clearCache()               — limpa cache de estatisticas
 */
class RatBiService
{
    private const CACHE_TTL = 3600;
    private const CACHE_PREFIX = 'rat.bi.';

    /** Contagem de ocorrências agrupadas por status. */
    public function getOcorrenciasPorStatus(): Collection
    {
        return Cache::remember(self::CACHE_PREFIX . 'status', self::CACHE_TTL, function () {
            return RatOcorrencia::selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->get()
                ->map(fn($row) => [
                    'status' => $row->status === 0 ? 'Rascunho' : 'Finalizado',
                    'total'  => $row->total,
                ]);
        });
    }

    /** Contagem mensal de ocorrências no ano corrente. */
    public function getOcorrenciasPorMes(): Collection
    {
        $year = now()->year;
        return Cache::remember(self::CACHE_PREFIX . "mes.{$year}", self::CACHE_TTL, function () {
            return RatOcorrencia::selectRaw('MONTH(created_at) as mes, count(*) as total')
                ->whereYear('created_at', now()->year)
                ->groupBy('mes')
                ->orderBy('mes')
                ->get();
        });
    }

    /** Distribuição de envolvidos por tipo de pessoa. */
    public function getEnvolvidosPorTipo(): Collection
    {
        return Cache::remember(self::CACHE_PREFIX . 'envolvidos', self::CACHE_TTL, function () {
            return RatRelatoEnvolvidos::selectRaw('g_tipo_pessoa, count(*) as total')
                ->whereNotNull('g_tipo_pessoa')
                ->groupBy('g_tipo_pessoa')
                ->get();
        });
    }

    /** Distribuição de recursos por tipo. */
    public function getRecursosPorTipo(): Collection
    {
        return Cache::remember(self::CACHE_PREFIX . 'recursos', self::CACHE_TTL, function () {
            return RatRelatoRecurso::selectRaw('recurso_tipo, count(*) as total')
                ->whereNotNull('recurso_tipo')
                ->groupBy('recurso_tipo')
                ->get();
        });
    }

    /** Limpa todo o cache de estatisticas RAT. */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_PREFIX . 'status');
        Cache::forget(self::CACHE_PREFIX . 'mes.' . now()->year);
        Cache::forget(self::CACHE_PREFIX . 'envolvidos');
        Cache::forget(self::CACHE_PREFIX . 'recursos');
    }
}
