<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes\Services;

use App\Modules\Decretacoes\Models\Processo;
use Illuminate\Support\Facades\DB;

/**
 * Service responsible for Processo statistics and dashboard metrics.
 *
 * Consolidado: 15 queries -> 3 queries via conditional aggregation.
 */
class ProcessoStatsService
{
    /**
     * Get dashboard statistics for DecretacoesStatsCards component.
     *
     * 1 query principal (conditional aggregation) + 1 municipios + 1 vigentes
     *
     * @return array<string, int>
     */
    public function getDashboardStatistics(): array
    {
        $stats = $this->getConsolidatedCounts();
        $municipios = $this->getMunicipiosAtingidosCounts();
        $vigentes = $this->getVigentesCounts();

        return array_merge($stats, $municipios, $vigentes);
    }

    /**
     * 1 query: total_eventos, registros, decretacoes (com breakdown ECP/SE).
     * Substitui 9 queries separadas.
     */
    private function getConsolidatedCounts(): array
    {
        $table = (new Processo())->getTable();

        $row = DB::table($table)
            ->whereNull('deleted_at')
            ->selectRaw("
                COUNT(*) as total_eventos,
                COUNT(CASE WHEN tipo_desastre = 'ECP' THEN 1 END) as total_eventos_ecp,
                COUNT(CASE WHEN tipo_desastre = 'SE' THEN 1 END) as total_eventos_se,
                COUNT(CASE WHEN reconhecimento = 'Registro' THEN 1 END) as registros,
                COUNT(CASE WHEN reconhecimento = 'Registro' AND tipo_desastre = 'ECP' THEN 1 END) as registros_ecp,
                COUNT(CASE WHEN reconhecimento = 'Registro' AND tipo_desastre = 'SE' THEN 1 END) as registros_se,
                COUNT(CASE WHEN reconhecimento != 'Registro' THEN 1 END) as decretacoes,
                COUNT(CASE WHEN reconhecimento != 'Registro' AND tipo_desastre = 'ECP' THEN 1 END) as decretacoes_ecp,
                COUNT(CASE WHEN reconhecimento != 'Registro' AND tipo_desastre = 'SE' THEN 1 END) as decretacoes_se
            ")
            ->first();

        return [
            'totalEventos'    => (int) $row->total_eventos,
            'totalEventosEcp' => (int) $row->total_eventos_ecp,
            'totalEventosSe'  => (int) $row->total_eventos_se,
            'registros'       => (int) $row->registros,
            'registrosEcp'    => (int) $row->registros_ecp,
            'registrosSe'     => (int) $row->registros_se,
            'decretacoes'     => (int) $row->decretacoes,
            'decretacoesEcp'  => (int) $row->decretacoes_ecp,
            'decretacoesSe'   => (int) $row->decretacoes_se,
        ];
    }

    /**
     * 1 query: municipios atingidos (distinct municipio_id via subquery).
     */
    private function getMunicipiosAtingidosCounts(): array
    {
        $table = (new Processo())->getTable();

        $rows = DB::select("
            SELECT
                COUNT(DISTINCT dm.municipio_id) as total,
                COUNT(DISTINCT CASE WHEN p.tipo_desastre = 'ECP' THEN dm.municipio_id END) as ecp,
                COUNT(DISTINCT CASE WHEN p.tipo_desastre = 'SE' THEN dm.municipio_id END) as se
            FROM dec_decreto_municipios dm
            INNER JOIN {$table} p ON p.id = dm.entrada_processos_id
            WHERE p.deleted_at IS NULL
              AND p.reconhecimento != 'Registro'
        ");

        $row = $rows[0];

        return [
            'municipiosAtingidos'    => (int) $row->total,
            'municipiosAtingidosEcp' => (int) $row->ecp,
            'municipiosAtingidosSe'  => (int) $row->se,
        ];
    }

    /**
     * 1 query: decretacoes vigentes (com breakdown ECP/SE).
     */
    private function getVigentesCounts(): array
    {
        $table = (new Processo())->getTable();

        $row = DB::table($table)
            ->whereNull('deleted_at')
            ->where('reconhecimento', '!=', 'Registro')
            ->where('reconhecimento', 'like', 'Reconhecido pelo Estado%')
            ->where(function ($q) {
                $q->whereNull('data_publicacao_mg')
                  ->orWhereRaw('DATE_ADD(data_publicacao_mg, INTERVAL prazo_vigencia DAY) >= CURDATE()');
            })
            ->selectRaw("
                COUNT(*) as total,
                COUNT(CASE WHEN tipo_desastre = 'ECP' THEN 1 END) as ecp,
                COUNT(CASE WHEN tipo_desastre = 'SE' THEN 1 END) as se
            ")
            ->first();

        return [
            'decretacoesVigentes'    => (int) $row->total,
            'decretacoesVigentesEcp' => (int) $row->ecp,
            'decretacoesVigentesSe'  => (int) $row->se,
        ];
    }

    /**
     * Get basic statistics (legacy method).
     *
     * @return array<string, int>
     */
    public function getStatistics(): array
    {
        $table = (new Processo())->getTable();

        $row = DB::table($table)
            ->whereNull('deleted_at')
            ->selectRaw("
                COUNT(*) as total,
                COUNT(CASE WHEN reconhecimento = 'Registro' THEN 1 END) as em_analise,
                COUNT(CASE WHEN reconhecimento LIKE 'Reconhecido%' THEN 1 END) as aprovados,
                COUNT(CASE WHEN reconhecimento = 'Nao reconhecido' THEN 1 END) as rejeitados
            ")
            ->first();

        return [
            'total'      => (int) $row->total,
            'em_analise' => (int) $row->em_analise,
            'aprovados'  => (int) $row->aprovados,
            'rejeitados' => (int) $row->rejeitados,
        ];
    }

    /**
     * Get the count of valid (vigentes) processos.
     */
    public function getVigentesCount(): int
    {
        return Processo::where(function ($q) {
            $q->whereNull('data_publicacao_mg')
              ->orWhereRaw('DATE_ADD(data_publicacao_mg, INTERVAL prazo_vigencia DAY) >= CURDATE()');
        })->count();
    }
}
