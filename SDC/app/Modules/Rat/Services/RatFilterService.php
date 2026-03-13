<?php

declare(strict_types=1);

namespace App\Modules\Rat\Services;

use App\Modules\Rat\DTOs\RatFilterDTO;
use Illuminate\Database\Eloquent\Builder;

/**
 * Responsabilidade única: construir filtros de query para RAT.
 * Só aplica filtros, sem acesso direto ao Model.
 */
class RatFilterService
{
    /**
     * Aplica todos os filtros ativos na query.
     * Máximo 5 linhas — delega para métodos privados.
     */
    public function apply(Builder $query, RatFilterDTO $filters): Builder
    {
        $this->applySearch($query, $filters->protocolo, $filters->criadoPor);
        $this->applyStatus($query, $filters->status);
        $this->applyDateRange($query, $filters->dataInicio, $filters->dataFim, $filters->ano);
        $this->applyLocation($query, $filters->municipio);

        return $query;
    }

    /**
     * Filtro de protocolo e criado_por via join com users.
     */
    private function applySearch(Builder $query, ?string $protocolo, ?string $criadoPor): void
    {
        if ($protocolo) {
            $query->where('protocolo', 'like', "%{$protocolo}%");
        }

        if ($criadoPor) {
            $query->whereHas('creator', fn(Builder $q) =>
                $q->where('name', 'like', "%{$criadoPor}%")
            );
        }
    }

    /**
     * Filtro de status exato.
     */
    private function applyStatus(Builder $query, ?string $status): void
    {
        if ($status) {
            $query->where('status', $status);
        }
    }

    /**
     * Filtro de intervalo de datas e ano.
     */
    private function applyDateRange(Builder $query, ?string $start, ?string $end, ?string $ano): void
    {
        if ($start) {
            $query->where('created_at', '>=', $start . ' 00:00:00');
        }

        if ($end) {
            $query->where('created_at', '<=', $end . ' 23:59:59');
        }

        if ($ano) {
            $query->whereYear('created_at', $ano);
        }
    }

    /**
     * Filtro de município via operador JSON do Laravel (portável MySQL/SQLite).
     */
    private function applyLocation(Builder $query, ?string $municipio): void
    {
        if ($municipio) {
            $query->where('local->municipio', 'like', "%{$municipio}%");
        }
    }
}
