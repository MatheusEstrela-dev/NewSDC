<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\Tdap\Models\CronoViagem
 */
class CronoViagemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'crono_caminhao_id' => $this->crono_caminhao_id,
            'status'            => $this->status,
            'validado'          => $this->validado,
            'data_registro'     => $this->data_registro?->toIso8601String(),
            'data_aprovacao'    => $this->data_aprovacao?->toIso8601String(),
            'obs'               => $this->obs,
            'obs_aprovacao'     => $this->obs_aprovacao,
            'validador'         => $this->whenLoaded('validador', fn () => [
                'id'   => $this->validador?->id,
                'name' => $this->validador?->name,
            ]),
            'cronograma_numero' => $this->whenLoaded('cronoCaminhao', fn () => $this->cronoCaminhao?->cronograma?->numero),
            'caminhao_placa'    => $this->whenLoaded('cronoCaminhao', fn () => $this->cronoCaminhao?->caminhao?->placa),
            'created_at'        => $this->created_at?->toIso8601String(),
        ];
    }
}
