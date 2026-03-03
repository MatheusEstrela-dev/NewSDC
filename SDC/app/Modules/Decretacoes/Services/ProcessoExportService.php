<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes\Services;

use App\Modules\Decretacoes\Models\Processo;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Service responsible for processing and exporting Processo data to Power BI.
 */
class ProcessoExportService
{
    // Categorias de exportacao
    private const CAT_DANOS_MATERIAIS = 'DANOS MATERIAIS';
    private const CAT_PREJUIZOS_PUBLICOS = 'PREJUIZOS ECONOMICOS PUBLICOS';
    private const CAT_PREJUIZOS_PRIVADOS = 'PREJUIZOS ECONOMICOS PRIVADOS';
    private const CATEGORIA_DANOS_HUMANOS = 1;

    // Mapeamento de item_id para tipo de dano humano
    private const DANOS_HUMANOS_MAP = [
        1 => 'obitos',
        2 => 'feridos',
        3 => 'feridos', // Historicamente agrupado no BD
        4 => 'desabrigados',
        5 => 'desalojados',
        6 => 'desaparecidos',
        7 => 'outros_afetados',
    ];

    // Tipos de decreto validos
    private const TIPOS_DECRETO_VALIDOS = ['SE', 'ECP'];

    public function __construct(
        private readonly ProcessoQueryService $queryService
    ) {
    }

    /**
     * Get normalized data for Power BI export.
     */
    public function getNormalizedDataForPowerBI(Request $request): array
    {
        $query = $this->queryService->applyFilters($request);

        if ($request->input('include_deleted', false)) {
            $query->withTrashed();
        }

        $entradas = $query->get();
        $normalizedData = [];

        foreach ($entradas as $entrada) {
            $entrada->load(['municipios', 'desastres']);
            $desastreTotals = $this->calculateDesastreTotalsForEntry($entrada);

            if ($entrada->municipios->isEmpty()) {
                $normalizedData[] = $this->buildExportRow($entrada, null, [], []);
                continue;
            }

            foreach ($entrada->municipios as $municipio) {
                $municipioTotals = $desastreTotals['por_municipio'][$municipio->id] ?? [];
                $danosHumanos = $desastreTotals['danos_humanos_por_municipio'][$municipio->id] ?? [];
                $normalizedData[] = $this->buildExportRow($entrada, $municipio, $municipioTotals, $danosHumanos);
            }
        }

        return $normalizedData;
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
        return [
            'id' => $entrada->id,
            'uf' => 'MG',
            'municipio' => $municipio?->p_nome ?? $municipio?->nome,
            'codigo_ibge' => $municipio?->Codmundv,
            'macroregiao' => $municipio?->macroregiao,
            'latitude' => $municipio?->latitude,
            'longitude' => $municipio?->longitude,
            'latitude_dec' => $municipio?->latitude_dec,
            'longitude_dec' => $municipio?->longitude_dec,
            'data_registro' => $entrada->data_entrada,
            'data_criacao' => $entrada->created_at,
            'deletado' => $entrada->trashed(),
            'data_delecao' => $entrada->deleted_at,
            'protocolo' => $entrada->n_protocolo_fide,
            'cobrade' => $entrada->tipo_desastre_cobrade,
            'tipo_desastre' => $entrada->tipo_desastre_nome,
            'status' => $entrada->reconhecimento,
            'data_fato' => $entrada->data_ocorrencia_desastre,
            'data_decreto_municipal' => $entrada->data_decreto_municipal,
            'data_publicacao_mg' => $entrada->data_publicacao_mg,
            'prazo_vigencia_dias' => $entrada->prazo_vigencia,
            'data_vencimento' => $entrada->data_vencimento,
            'dias_restantes' => $entrada->dias_restantes,
            'tipo_decreto' => $this->mapearTipoDecreto($entrada->situacao_anormalidade),
            'processo' => $entrada->processo,
            'analista' => $entrada->analista,
        ];
    }

    private function buildDanosHumanosRow(array $danosHumanos): array
    {
        return [
            'obitos' => $danosHumanos['obitos'] ?? 0,
            'feridos' => $danosHumanos['feridos'] ?? 0,
            'desalojados' => $danosHumanos['desalojados'] ?? 0,
            'desabrigados' => $danosHumanos['desabrigados'] ?? 0,
            'desaparecidos' => $danosHumanos['desaparecidos'] ?? 0,
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
        return [
            'danos_materiais_danificadas' => $municipioTotals[self::CAT_DANOS_MATERIAIS]['Quantidades danificadas'] ?? 0,
            'danos_materiais_destruidas' => $municipioTotals[self::CAT_DANOS_MATERIAIS]['Quantidades destruidas'] ?? 0,
            'danos_materiais_valor' => $municipioTotals[self::CAT_DANOS_MATERIAIS]['Valor (R$)'] ?? 0,
            'prejuizos_publicos_valor' => $municipioTotals[self::CAT_PREJUIZOS_PUBLICOS]['Valor do prejuizo (R$)'] ?? 0,
            'prejuizos_privados_valor' => $municipioTotals[self::CAT_PREJUIZOS_PRIVADOS]['Valor do prejuizo (R$)'] ?? 0,
        ];
    }

    /**
     * Calculate desastre totals for a single entry.
     */
    private function calculateDesastreTotalsForEntry(Processo $entrada): array
    {
        $processoIds = collect([$entrada->id]);

        $allTotals = DB::table('dec_entrada_categoria_desastres as ecd')
            ->join('dec_entrada_desastres as ed', 'ecd.id', '=', 'ed.entrada_categoria_desastre_id')
            ->join('dec_desastre_item_campos as dic', 'ed.item_campo_id', '=', 'dic.id')
            ->join('dec_desastre_categorias as dc', 'ecd.categoria_id', '=', 'dc.id')
            ->join('cedec_municipio as m', 'ed.municipio_id', '=', 'm.id')
            ->whereIn('ecd.entrada_processo_id', $processoIds)
            ->whereIn('dic.tipo', ['number', 'currency'])
            ->select(
                'ed.municipio_id',
                'dc.titulo as categoria_titulo',
                'dic.titulo as desastre_campo_titulo',
                'dic.tipo',
                DB::raw('SUM(ed.valor) as total_valor')
            )
            ->groupBy('ed.municipio_id', 'dc.titulo', 'dic.titulo', 'dic.tipo')
            ->get();

        $groupedByMunicipio = $allTotals->groupBy('municipio_id')->map(function ($municipioItems) {
            return $municipioItems->groupBy('categoria_titulo')->map(function ($categoriaItems) {
                return $categoriaItems->keyBy('desastre_campo_titulo')->map(function ($item) {
                    return $item->tipo === 'currency' ? (float) $item->total_valor : (int) $item->total_valor;
                });
            });
        });

        $danosHumanosByMunicipio = $this->calculateDanosHumanos($processoIds);

        return [
            'por_municipio' => $groupedByMunicipio->toArray(),
            'danos_humanos_por_municipio' => $danosHumanosByMunicipio->toArray()
        ];
    }

    /**
     * Calculate danos humanos for processos.
     */
    private function calculateDanosHumanos(Collection $processoIds): Collection
    {
        $danosHumanos = DB::table('dec_entrada_desastres as ed')
            ->join('dec_entrada_categoria_desastres as ecd', 'ed.entrada_categoria_desastre_id', '=', 'ecd.id')
            ->join('dec_desastre_item_campos as dic', 'ed.item_campo_id', '=', 'dic.id')
            ->join('dec_desastre_items as di', 'dic.desastre_item_id', '=', 'di.id')
            ->join('dec_desastre_categorias as dc', 'di.categoria_id', '=', 'dc.id')
            ->whereIn('ecd.entrada_processo_id', $processoIds)
            ->where('dc.id', self::CATEGORIA_DANOS_HUMANOS)
            ->whereNull('ed.deleted_at')
            ->select(
                'ed.municipio_id',
                'di.id as item_id',
                DB::raw('CAST(COALESCE(ed.valor, 0) AS UNSIGNED) as valor_numerico')
            )
            ->get();

        return $danosHumanos->groupBy('municipio_id')->map(function ($municipioItems) {
            $result = array_fill_keys(array_unique(array_values(self::DANOS_HUMANOS_MAP)), 0);

            foreach ($municipioItems as $item) {
                $key = self::DANOS_HUMANOS_MAP[(int) $item->item_id] ?? null;
                if ($key) {
                    $result[$key] += (int) $item->valor_numerico;
                }
            }

            return $result;
        });
    }

    /**
     * Map situacao anormalidade to tipo decreto.
     */
    private function mapearTipoDecreto(?string $situacaoAnormalidade): ?string
    {
        return in_array($situacaoAnormalidade, self::TIPOS_DECRETO_VALIDOS, true) ? $situacaoAnormalidade : null;
    }
}
