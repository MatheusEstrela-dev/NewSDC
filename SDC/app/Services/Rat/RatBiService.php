<?php

declare(strict_types=1);

namespace App\Services\Rat;

use App\Models\Rat\RatOcorrencia;
use App\Models\Rat\Relatos\RatRelatoDadosGerais;
use App\Models\Rat\Relatos\RatRelatoEnvolvidos;
use App\Models\Rat\Relatos\RatRelatoRecurso;
use Illuminate\Support\Collection;

/**
 * Agrega dados das ocorrências RAT para consumo em dashboards BI.
 *
 * Métodos públicos:
 *   getOcorrenciasPorStatus()  — contagem agrupada por status
 *   getOcorrenciasPorMes()     — contagem mensal do ano corrente
 *   getEnvolvidosPorTipo()     — distribuição por tipo de pessoa
 *   getRecursosPorTipo()       — distribuição por tipo de recurso
 */
class RatBiService
{
    /** Contagem de ocorrências agrupadas por status. */
    public function getOcorrenciasPorStatus(): Collection
    {
        return RatOcorrencia::selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->get()
            ->map(fn($row) => [
                'status' => $row->status === 0 ? 'Rascunho' : 'Finalizado',
                'total'  => $row->total,
            ]);
    }

    /** Contagem mensal de ocorrências no ano corrente. */
    public function getOcorrenciasPorMes(): Collection
    {
        return RatOcorrencia::selectRaw('MONTH(created_at) as mes, count(*) as total')
            ->whereYear('created_at', now()->year)
            ->groupBy('mes')
            ->orderBy('mes')
            ->get();
    }

    /** Distribuição de envolvidos por tipo de pessoa. */
    public function getEnvolvidosPorTipo(): Collection
    {
        return RatRelatoEnvolvidos::selectRaw('g_tipo_pessoa, count(*) as total')
            ->whereNotNull('g_tipo_pessoa')
            ->groupBy('g_tipo_pessoa')
            ->get();
    }

    /** Distribuição de recursos por tipo. */
    public function getRecursosPorTipo(): Collection
    {
        return RatRelatoRecurso::selectRaw('recurso_tipo, count(*) as total')
            ->whereNotNull('recurso_tipo')
            ->groupBy('recurso_tipo')
            ->get();
    }
}
