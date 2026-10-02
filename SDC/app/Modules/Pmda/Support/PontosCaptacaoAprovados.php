<?php

declare(strict_types=1);

namespace App\Modules\Pmda\Support;

use App\Modules\Pmda\Enums\PmdaStatus;
use App\Modules\Pmda\Models\PmdaPlano;
use App\Modules\Pmda\Models\PmdaPonto;
use App\Modules\Tdap\Contracts\PontosCaptacaoDoPmda;
use App\Modules\Tdap\DTOs\VinculoPmdaDTO;

/**
 * Pontos de captacao que o PMDA vigente de cada municipio autoriza.
 *
 * PMDA VIGENTE e o plano APROVADO ou ATENDIDO mais recente do municipio. So o
 * mais recente vale porque o plano e refeito por copia a cada ciclo: um
 * municipio chega a ter varios aprovados, e o ponto que saiu (ou secou) no
 * plano novo nao pode continuar valendo pelo plano antigo.
 *
 * Do vinculo plano<->ponto so entra a situacao ATIVO -- SECO e justamente o
 * ponto de onde o caminhao nao tem como tirar agua. Do ponto, so os ativos e
 * nao excluidos, e do mesmo municipio do plano (o vinculo "ponto existente"
 * do PMDA nao confere municipio).
 */
final class PontosCaptacaoAprovados implements PontosCaptacaoDoPmda
{
    public const SITUACAO_ATIVO = 'ATIVO';

    /** Situacoes em que o plano ja passou pela aprovacao da CEDEC. */
    private const STATUS_APROVADOS = [PmdaStatus::APROVADO, PmdaStatus::ATENDIDO];

    public function vinculosVigentes(array $municipioIds): array
    {
        $municipioIds = array_values(array_unique(array_filter(array_map('intval', $municipioIds))));
        if ($municipioIds === []) {
            return [];
        }

        $planos = $this->planosVigentes($municipioIds);
        if ($planos === []) {
            return [];
        }

        $pontos = PmdaPonto::query()
            ->join('pmda_plano_ponto as pp', 'pp.ponto_id', '=', 'pip_pmda_ponto.id')
            ->whereIn('pp.pmda_plano_id', array_keys($planos))
            ->where('pp.situacao', self::SITUACAO_ATIVO)
            ->where('pip_pmda_ponto.ativo', true)
            ->get(['pip_pmda_ponto.id', 'pip_pmda_ponto.municipio_id', 'pp.pmda_plano_id']);

        $vinculos = [];
        foreach ($pontos as $ponto) {
            $plano = $planos[(int) $ponto->pmda_plano_id];
            if ((int) $ponto->municipio_id !== (int) $plano->municipio_id) {
                continue;
            }

            $vinculos[(int) $ponto->id] = new VinculoPmdaDTO(
                pontoId:     (int) $ponto->id,
                municipioId: (int) $plano->municipio_id,
                pmdaPlanoId: (int) $plano->id,
                protocolo:   $plano->protocolo !== null ? (string) $plano->protocolo : null,
            );
        }

        return $vinculos;
    }

    public function protocolos(array $pmdaPlanoIds): array
    {
        $pmdaPlanoIds = array_values(array_unique(array_filter(array_map('intval', $pmdaPlanoIds))));
        if ($pmdaPlanoIds === []) {
            return [];
        }

        // withTrashed: o plano pode ter sido excluido depois de autorizar o ponto.
        return PmdaPlano::withTrashed()
            ->whereKey($pmdaPlanoIds)
            ->pluck('protocolo', 'id')
            ->map(fn ($protocolo) => $protocolo !== null ? (string) $protocolo : null)
            ->all();
    }

    /**
     * O plano aprovado mais recente de cada municipio, indexado pelo id.
     *
     * Ordena no PHP depois de uma consulta so: `data_aprov` pode vir nula (plano
     * aprovado fora do fluxo) e o "DESC" do Postgres poe nulo PRIMEIRO; aqui o
     * nulo perde para qualquer data e o id desempata.
     *
     * @param  list<int>  $municipioIds
     * @return array<int, PmdaPlano>
     */
    private function planosVigentes(array $municipioIds): array
    {
        return PmdaPlano::query()
            ->whereIn('municipio_id', $municipioIds)
            ->whereIn('status', array_map(fn (PmdaStatus $s) => $s->value, self::STATUS_APROVADOS))
            ->get(['id', 'municipio_id', 'protocolo', 'data_aprov'])
            ->sortByDesc(fn (PmdaPlano $p) => [$p->data_aprov?->getTimestamp() ?? PHP_INT_MIN, $p->id])
            ->unique('municipio_id')
            ->keyBy('id')
            ->all();
    }
}
