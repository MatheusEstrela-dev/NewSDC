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
        ];
    }
}
