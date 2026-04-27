<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes\Services;

use App\Modules\Decretacoes\Constants\DesastreConstants;
use App\Modules\Decretacoes\Models\Processo;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Service responsible for processing and exporting Processo data to Power BI.
 */
class ProcessoExportBIService
{
    private const TIPOS_DECRETO_VALIDOS = ['SE', 'ECP'];

    public function __construct(
        private readonly ProcessoQueryService $queryService
    ) {
    }

    /**
     * Get normalized data for Power BI export.
     *
     * PERFORMANCE: Carrega todos os processos com eager load (municipios/desastres)
     * e calcula totais em 2 queries batch para todos os IDs — sem N+1.
     */
    public function getNormalizedDataForPowerBI(Request $request): array
    {
        $query = $this->queryService->applyFilters($request);

        if ($request->input('include_deleted', false)) {
            $query->withTrashed();
        }

        $entradas = $query->with(['municipios', 'desastres'])->get();

        if ($entradas->isEmpty()) {
            return [];
        }

        $allIds = $entradas->pluck('id');

        $desastreTotalsBatch = $this->calculateDesastreTotalsBatch($allIds);
        $danosHumanosBatch   = $this->calculateDanosHumanosBatch($allIds);

        $normalizedData = [];

        foreach ($entradas as $entrada) {
            $pid = $entrada->id;

            if ($entrada->municipios->isEmpty()) {
                $normalizedData[] = $this->buildExportRow($entrada, null, [], []);
                continue;
            }

            foreach ($entrada->municipios as $municipio) {
                $municipioTotals  = $desastreTotalsBatch[$pid][$municipio->id] ?? [];
                $danosHumanos     = $danosHumanosBatch[$pid][$municipio->id] ?? [];
                $normalizedData[] = $this->buildExportRow($entrada, $municipio, $municipioTotals, $danosHumanos);
            }
        }

        return $normalizedData;
    }

    /**
     * Calcula totais de danos materiais e prejuizos para todos os processos em uma unica query batch.
     *
     * Retorna: [processo_id][municipio_id][categoria_titulo][campo_titulo] = valor
     */
    private function calculateDesastreTotalsBatch(Collection $processoIds): array
    {
        $rows = DB::table('dec_entrada_categoria_desastres as ecd')
            ->join('dec_entrada_desastres as ed', 'ecd.id', '=', 'ed.entrada_categoria_desastre_id')
            ->join('dec_desastre_item_campos as dic', 'ed.item_campo_id', '=', 'dic.id')
            ->join('dec_desastre_categorias as dc', 'ecd.categoria_id', '=', 'dc.id')
            ->whereIn('ecd.entrada_processo_id', $processoIds)
            ->whereIn('dic.tipo', ['number', 'currency'])
            ->select(
                'ecd.entrada_processo_id as processo_id',
                'ed.municipio_id',
                'dc.titulo as categoria_titulo',
                'dic.titulo as desastre_campo_titulo',
                'dic.tipo',
                DB::raw('SUM(ed.valor) as total_valor')
            )
            ->groupBy('ecd.entrada_processo_id', 'ed.municipio_id', 'dc.titulo', 'dic.titulo', 'dic.tipo')
            ->get();

        $result = [];

        foreach ($rows as $row) {
            $pid   = $row->processo_id;
            $mid   = $row->municipio_id;
            $cat   = $row->categoria_titulo;
            $campo = $row->desastre_campo_titulo;
            $valor = $row->tipo === 'currency' ? (float) $row->total_valor : (int) $row->total_valor;

            $result[$pid][$mid][$cat][$campo] = $valor;
        }

        return $result;
    }

    /**
     * Calcula danos humanos para todos os processos em uma unica query batch.
     *
     * Retorna: [processo_id][municipio_id] = ['obitos' => int, 'feridos' => int, ...]
     */
    private function calculateDanosHumanosBatch(Collection $processoIds): array
    {
        $rows = DB::table('dec_entrada_desastres as ed')
            ->join('dec_entrada_categoria_desastres as ecd', 'ed.entrada_categoria_desastre_id', '=', 'ecd.id')
            ->join('dec_desastre_item_campos as dic', 'ed.item_campo_id', '=', 'dic.id')
            ->join('dec_desastre_items as di', 'dic.desastre_item_id', '=', 'di.id')
            ->join('dec_desastre_categorias as dc', 'di.categoria_id', '=', 'dc.id')
            ->whereIn('ecd.entrada_processo_id', $processoIds)
            ->where('dc.id', DesastreConstants::CATEGORIA_DANOS_HUMANOS_ID)
            ->whereNull('ed.deleted_at')
            ->select(
                'ecd.entrada_processo_id as processo_id',
                'ed.municipio_id',
                'di.id as item_id',
                DB::raw('CAST(COALESCE(ed.valor, 0) AS BIGINT) as valor_numerico')
            )
            ->get();

        $emptyDanos = array_fill_keys(array_unique(array_values(DesastreConstants::DANOS_HUMANOS_MAP)), 0);
        $result = [];

        foreach ($rows as $item) {
            $pid = $item->processo_id;
            $mid = $item->municipio_id;
            $key = DesastreConstants::DANOS_HUMANOS_MAP[(int) $item->item_id] ?? null;

            if (!$key) {
                continue;
            }

            if (!isset($result[$pid][$mid])) {
                $result[$pid][$mid] = $emptyDanos;
            }

            $result[$pid][$mid][$key] += (int) $item->valor_numerico;
        }

        return $result;
    }

    /**
     * Build a row for Power BI export.
     */
    private function buildExportRow(Processo $entrada, ?object $municipio, array $municipioTotals, array $danosHumanos): array
    {
        $row = $this->buildBaseExportRow($entrada, $municipio);
        $row = array_merge($row, $this->buildDanosHumanosRow($danosHumanos));
        $row['danos_humanos_quantidade'] = $this->sumDanosHumanos($row);
        $row = array_merge($row, $this->buildDanosMateriaisRow($municipioTotals));

        return $row;
    }

    private function buildBaseExportRow(Processo $entrada, ?object $municipio): array
    {
        $lat = $municipio?->latitude;
        $lng = $municipio?->longitude;

        return [
            'id'                    => $entrada->id,
            'uf'                    => 'MG',
            'municipio'             => $municipio?->nome,
            'codigo_ibge'           => $municipio?->codigo_ibge,
            'macroregiao'           => $municipio?->mesorregiao ?? $municipio?->regiao,
            'latitude'              => $lat !== null ? (string) intval($lat) . '.' : null,
            'longitude'             => $lng !== null ? (string) intval($lng) . '.' : null,
            'latitude_dec'          => $lat !== null ? (float) $lat : null,
            'longitude_dec'         => $lng !== null ? (float) $lng : null,
            'data_registro'         => $entrada->data_entrada,
            'data_criacao'          => $entrada->created_at,
            'deletado'              => $entrada->trashed(),
            'data_delecao'          => $entrada->deleted_at,
            'protocolo'             => $entrada->n_protocolo_fide,
            'cobrade'               => $entrada->tipo_desastre_cobrade,
            'tipo_desastre'         => $entrada->tipo_desastre_nome,
            'status'                => $entrada->reconhecimento,
            'data_fato'             => $entrada->data_ocorrencia_desastre,
            'data_decreto_municipal'=> $entrada->data_decreto_municipal,
            'data_publicacao_mg'    => $entrada->data_publicacao_mg,
            'prazo_vigencia_dias'   => $entrada->prazo_vigencia,
            'data_vencimento'       => $entrada->data_vencimento,
            'dias_restantes'        => $entrada->dias_restantes,
            'tipo_decreto'          => $this->mapearTipoDecreto($entrada->situacao_anormalidade),
            'processo'              => $entrada->processo,
            'analista'              => $entrada->analista,
        ];
    }

    private function buildDanosHumanosRow(array $danosHumanos): array
    {
        return [
            'obitos'          => $danosHumanos['obitos'] ?? 0,
            'feridos'         => $danosHumanos['feridos'] ?? 0,
            'desalojados'     => $danosHumanos['desalojados'] ?? 0,
            'desabrigados'    => $danosHumanos['desabrigados'] ?? 0,
            'desaparecidos'   => $danosHumanos['desaparecidos'] ?? 0,
            'outros_afetados' => $danosHumanos['outros_afetados'] ?? 0,
        ];
    }

    private function sumDanosHumanos(array $row): int
    {
        return $row['obitos'] + $row['feridos'] + $row['desalojados']
             + $row['desabrigados'] + $row['desaparecidos'] + $row['outros_afetados'];
    }

    private function buildDanosMateriaisRow(array $municipioTotals): array
    {
        $dm  = $municipioTotals[DesastreConstants::CAT_DANOS_MATERIAIS] ?? [];
        $pp  = $municipioTotals[DesastreConstants::CAT_PREJUIZOS_PUBLICOS] ?? [];
        $ppv = $municipioTotals[DesastreConstants::CAT_PREJUIZOS_PRIVADOS] ?? [];

        return [
            'danos_materiais_danificadas' => $dm['Quantidades danificadas'] ?? 0,
            'danos_materiais_destruidas'  => $dm['Quantidades destruídas'] ?? $dm['Quantidades destruidas'] ?? 0,
            'danos_materiais_valor'       => $dm['Valor (R$)'] ?? 0,
            'prejuizos_publicos_valor'    => $pp['Valor do prejuízo (R$)'] ?? $pp['Valor do prejuizo (R$)'] ?? 0,
            'prejuizos_privados_valor'    => $ppv['Valor do prejuízo (R$)'] ?? $ppv['Valor do prejuizo (R$)'] ?? 0,
        ];
    }

    /**
     * Map situacao anormalidade to tipo decreto.
     */
    private function mapearTipoDecreto(?string $situacaoAnormalidade): ?string
    {
        return in_array($situacaoAnormalidade, self::TIPOS_DECRETO_VALIDOS, true) ? $situacaoAnormalidade : null;
    }
}
