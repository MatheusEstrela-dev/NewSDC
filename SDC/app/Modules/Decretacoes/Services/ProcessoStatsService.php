<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes\Services;

use App\Modules\Decretacoes\Models\DecretoMunicipio;
use App\Modules\Decretacoes\Models\Processo;
use Illuminate\Database\Eloquent\Builder;

/**
 * Service responsible for Processo statistics and dashboard metrics.
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
    /**
     * Get dashboard statistics for DecretacoesStatsCards component.
     *
     * Retorna estrutura compativel com o componente Vue:
     * - totalEventos, totalEventosEcp, totalEventosSe
     * - registros, registrosEcp, registrosSe
     * - decretacoes, decretacoesEcp, decretacoesSe
     * - municipiosAtingidos, municipiosAtingidosEcp, municipiosAtingidosSe
     * - decretacoesVigentes, decretacoesVigentesEcp, decretacoesVigentesSe
     *
     * @return array<string, int>
     */
    public function getDashboardStatistics(): array
    {
        $baseQuery = Processo::query();

        return [
            // Total de Eventos
            'totalEventos'    => $this->getTotalEventos($baseQuery),
            'totalEventosEcp' => $this->getTotalEventos($baseQuery, 'ECP'),
            'totalEventosSe'  => $this->getTotalEventos($baseQuery, 'SE'),

            // Registros (reconhecimento = 'Registro')
            'registros'    => $this->getRegistros($baseQuery),
            'registrosEcp' => $this->getRegistros($baseQuery, 'ECP'),
            'registrosSe'  => $this->getRegistros($baseQuery, 'SE'),

            // Decretacoes (reconhecimento != 'Registro')
            'decretacoes'    => $this->getDecretacoes($baseQuery),
            'decretacoesEcp' => $this->getDecretacoes($baseQuery, 'ECP'),
            'decretacoesSe'  => $this->getDecretacoes($baseQuery, 'SE'),

            // Municipios Atingidos (distinct municipio_id)
            'municipiosAtingidos'    => $this->getMunicipiosAtingidos($baseQuery),
            'municipiosAtingidosEcp' => $this->getMunicipiosAtingidos($baseQuery, 'ECP'),
            'municipiosAtingidosSe'  => $this->getMunicipiosAtingidos($baseQuery, 'SE'),

            // Decretacoes Vigentes
            'decretacoesVigentes'    => $this->getDecretacoesVigentes($baseQuery),
            'decretacoesVigentesEcp' => $this->getDecretacoesVigentes($baseQuery, 'ECP'),
            'decretacoesVigentesSe'  => $this->getDecretacoesVigentes($baseQuery, 'SE'),
        ];
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
              ->orWhereRaw('DATE_ADD(data_publicacao_mg, INTERVAL prazo_vigencia DAY) >= CURDATE()');
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
        return Processo::where(function ($q) {
            $q->whereNull('data_publicacao_mg')
              ->orWhereRaw('DATE_ADD(data_publicacao_mg, INTERVAL prazo_vigencia DAY) >= CURDATE()');
        })->count();
    }
}
