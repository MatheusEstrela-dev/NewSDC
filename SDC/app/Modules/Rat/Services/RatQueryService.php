<?php

declare(strict_types=1);

namespace App\Modules\Rat\Services;

use App\Modules\Rat\Models\RatOcorrencia;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Service responsável por consultas e filtragem de RATs.
 *
 * FLUXO: Filtros (Request) → RatQueryService → Builder → Banco
 *
 * Extrai a lógica de query do controller, seguindo o mesmo padrão
 * de ProcessoQueryService no módulo de Decretações.
 */
class RatQueryService
{
    private const FILTER_PARAMS = [
        'protocolo', 'status', 'ano', 'data_inicio', 'data_fim',
        'municipio', 'uf', 'numero_bos',
    ];

    /**
     * Aplica filtros e retorna um Builder pronto para paginação ou exportação.
     */
    public function applyFilters(Request $request): Builder
    {
        $query = RatOcorrencia::query()->orderByDesc('created_at');

        if ($protocolo = $request->input('protocolo')) {
            $query->where('numero_bos', 'like', '%' . $protocolo . '%');
        }

        if ($numeroBos = $request->input('numero_bos')) {
            $query->where('numero_bos', 'like', '%' . $numeroBos . '%');
        }

        if ($request->filled('status')) {
            $statusValue = $request->input('status');
            // Aceita string (em_andamento/finalizado) ou int (0/1)
            if ($statusValue === 'finalizado') {
                $query->where('status', 1);
            } elseif ($statusValue === 'em_andamento') {
                $query->where('status', '!=', 1);
            } else {
                $query->where('status', $statusValue);
            }
        }

        if ($ano = $request->input('ano')) {
            $query->whereYear('created_at', (int) $ano);
        }

        if ($dataInicio = $request->input('data_inicio')) {
            $query->whereDate('created_at', '>=', $dataInicio);
        }

        if ($dataFim = $request->input('data_fim')) {
            $query->whereDate('created_at', '<=', $dataFim);
        }

        return $query;
    }

    /**
     * Verifica se há filtros ativos no request.
     */
    public function hasActiveFilters(Request $request): bool
    {
        return $request->hasAny(self::FILTER_PARAMS);
    }

    /**
     * Retorna resumo dos filtros aplicados (para o campo meta.filtros_aplicados).
     */
    public function getAppliedFiltersSummary(Request $request): array
    {
        return $request->only(self::FILTER_PARAMS);
    }
}
