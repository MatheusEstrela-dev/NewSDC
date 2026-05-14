<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\Tdap\Models\Historico
 */
class HistoricoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'data_evento' => $this->data_evento?->toIso8601String(),
            'tipo_evento' => $this->tipo_evento,
            'entity_type' => $this->entity_type,
            'entity_id'   => $this->entity_id,
            'obs'         => $this->obs,
            'payload'     => $this->payload,
            'user'        => $this->whenLoaded('user', fn () => [
                'id'   => $this->user?->id,
                'name' => $this->user?->name,
            ]),
        ];
    }
}
