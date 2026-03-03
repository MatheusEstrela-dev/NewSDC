<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes\Services;

use App\Modules\Decretacoes\Models\Processo;

/**
 * Service responsible for Processo statistics and dashboard metrics.
 */
class ProcessoStatsService
{
    /**
     * Get basic statistics for the Decretacoes module.
     *
     * @return array<string, int>
     */
    public function getStatistics(): array
    {
        return [
            'total'      => Processo::count(),
            'em_analise' => Processo::where('reconhecimento', 'Registro')->count(),
            'aprovados'  => Processo::where('reconhecimento', 'like', 'Reconhecido%')->count(),
            'rejeitados' => Processo::where('reconhecimento', 'Não reconhecido')->count(),
        ];
    }

    /**
     * Get detailed dashboard statistics.
     *
     * @return array<string, int>
     */
    public function getDashboardStatistics(): array
    {
        return [
            'total_processos'      => Processo::count(),
            'processos_vigentes'   => $this->getVigentesCount(),
            'processos_municipais' => Processo::where('processo', 'MUNICIPAL')->count(),
            'processos_estaduais'  => Processo::where('processo', 'ESTADUAL')->count(),
        ];
    }

    /**
     * Get the count of valid (vigentes) processos.
     *
     * @return int
     */
    public function getVigentesCount(): int
    {
        return Processo::where(function ($q) {
            $q->whereNull('data_publicacao_mg')
              ->orWhereRaw('DATE_ADD(data_publicacao_mg, INTERVAL prazo_vigencia DAY) >= CURDATE()');
        })->count();
    }
}
