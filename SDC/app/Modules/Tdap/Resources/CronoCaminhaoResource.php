<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\Tdap\Models\CronoCaminhao
 */
class CronoCaminhaoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'cronograma_id'  => $this->cronograma_id,
            'caminhao_id'    => $this->caminhao_id,
            'comunidade_id'  => $this->comunidade_id,
            'agua_prevista'  => (float) $this->agua_prevista,
            'num_viagens'    => (int) $this->num_viagens,
            'agua_entregue'  => (float) $this->agua_entregue,
            'vr_total'       => (float) $this->vr_total,
            'ordem'          => (int) $this->ordem,
            'percentual'     => $this->percentual_entregue,
            'caminhao'       => $this->whenLoaded('caminhao', fn () => [
                'id'            => $this->caminhao?->id,
                'placa'         => $this->caminhao?->placa,
                'marca'         => $this->caminhao?->marca,
                'modelo'        => $this->caminhao?->modelo,
                'capacidade_m3' => (float) ($this->caminhao?->capacidade_m3 ?? 0),
                'ativo'         => (bool) ($this->caminhao?->ativo ?? false),
            ]),
            'viagens_validadas_count' => (int) ($this->viagens_validadas_count ?? 0),
            'viagens_pendentes_count' => (int) ($this->viagens_pendentes_count ?? 0),
            'vistoria_vence_durante_o_cronograma' => $this->vistoriaVenceNoMeio(),
            'vistoria_valida_ate' => $this->vistoriaValidaAte()?->toDateString(),
        ];
    }

    /**
     * A vistoria do caminhao vence antes do fim do cronograma?
     *
     * SINALIZACAO, e nao bloqueio. O guard de
     * CronogramaService::caminhoesSemVistoriaAte ja impede ATIVAR um cronograma
     * assim, mas ele so roda na ativacao: cronograma ja ativo cuja vistoria
     * vence no meio do caminho passaria despercebido. Medido na base ao ligar a
     * regra: 14 dos 79 cronogramas ativos estavam nessa situacao.
     */
    private function vistoriaVenceNoMeio(): bool
    {
        $fim = $this->cronograma?->dt_final_efetiva;
        $validoAte = $this->vistoriaValidaAte();

        if ($fim === null || $validoAte === null) {
            return false;
        }

        return $validoAte->lessThan($fim->copy()->startOfDay());
    }

    /** Ultimo dia coberto pela vistoria aprovada mais recente do caminhao. */
    private function vistoriaValidaAte(): ?\Carbon\Carbon
    {
        return $this->caminhao?->ultimaVistoriaAprovada?->valido_ate;
    }
}
