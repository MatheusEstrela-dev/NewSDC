<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes\Resources;

use Illuminate\Http\Request;

/**
 * Resource para estrutura plana legada do endpoint GET /api/v1/decretacoes.
 *
 * FLUXO:
 *   Processo (Model com _geo e totais injetados) -> ProcessoFlatResource -> JSON plano
 *
 * HERDA helpers de ProcessoResource: safeGet, safeGetInt, formatDate,
 * getTipoDesastreCobrade, getDataVencimento, getDiasRestantes.
 *
 * PREREQUISITOS: o Processo precisa ter sido enriquecido com:
 *   - setAttribute('_geo', [...]) via ProcessoQueryService::enrichWithGeoData()
 *   - totais via ProcessoQueryService::enrichPaginatorWithTotais()
 */
class ProcessoFlatResource extends ProcessoResource
{
    public function toArray(Request $request): array
    {
        $geo            = $this->safeGet('_geo') ?? [];
        $totais         = $this->safeGet('totais') ?? [];
        $geral          = $totais['geral'] ?? [];
        $danosHumanos   = $geral['danos_humanos'] ?? [];
        $danosMateriais = $geral['danos_materiais'] ?? [];
        $prejPublicos   = $geral['prejuizos_publicos'] ?? [];
        $prejPrivados   = $geral['prejuizos_privados'] ?? [];

        return [
            'id'                          => $this->id,
            'uf'                          => $geo['uf'] ?? 'MG',
            'municipio'                   => $geo['municipio'] ?? null,
            'codigo_ibge'                 => $geo['codigo_ibge'] ?? null,
            'macroregiao'                 => $geo['macroregiao'] ?? null,
            'latitude'                    => $geo['latitude'] ?? null,
            'longitude'                   => $geo['longitude'] ?? null,
            'latitude_dec'                => $geo['latitude_dec'] ?? null,
            'longitude_dec'               => $geo['longitude_dec'] ?? null,
            'data_registro'               => $this->formatDate('data_entrada'),
            'data_criacao'                => $this->created_at?->toIso8601String(),
            'deletado'                    => $this->deleted_at !== null,
            'data_delecao'                => $this->deleted_at?->toIso8601String(),
            'protocolo'                   => $this->safeGet('n_protocolo_fide'),
            'cobrade'                     => $this->getTipoDesastreCobrade(),
            'tipo_desastre'               => $this->safeGet('tipo_desastre'),
            'status'                      => $this->safeGet('reconhecimento'),
            'data_fato'                   => $this->formatDate('data_ocorrencia_desastre'),
            'data_decreto_municipal'      => $this->formatDate('data_decreto_municipal'),
            'data_publicacao_mg'          => $this->formatDate('data_publicacao_mg'),
            'prazo_vigencia_dias'         => $this->safeGetInt('prazo_vigencia'),
            'data_vencimento'             => $this->getDataVencimento()?->format('Y-m-d'),
            'dias_restantes'              => $this->getDiasRestantes(),
            'tipo_decreto'                => $this->safeGet('tipo_decreto'),
            'processo'                    => $this->safeGet('processo'),
            'analista'                    => $this->safeGet('analista'),
            'obitos'                      => (int) ($danosHumanos['obitos'] ?? 0),
            'feridos'                     => (int) ($danosHumanos['feridos'] ?? 0),
            'desalojados'                 => (int) ($danosHumanos['desalojados'] ?? 0),
            'desabrigados'                => (int) ($danosHumanos['desabrigados'] ?? 0),
            'desaparecidos'               => (int) ($danosHumanos['desaparecidos'] ?? 0),
            'outros_afetados'             => (int) ($danosHumanos['outros_afetados'] ?? 0),
            'danos_humanos_quantidade'    => (int) ($danosHumanos['total'] ?? 0),
            'danos_materiais_danificadas' => (int) ($danosMateriais['danificadas'] ?? 0),
            'danos_materiais_destruidas'  => (int) ($danosMateriais['destruidas'] ?? 0),
            'danos_materiais_valor'       => $danosMateriais['valor'] ?? 0,
            'prejuizos_publicos_valor'    => $prejPublicos['total'] ?? 0,
            'prejuizos_privados_valor'    => $prejPrivados['total'] ?? 0,
        ];
    }
}
