<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Resources;

use App\Modules\Tdap\Support\EntidadeDoHistorico;
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
            'tipo_rotulo' => EntidadeDoHistorico::rotuloDoEvento((string) $this->tipo_evento),
            'entity_type' => $this->entity_type,
            'entity_id'   => $this->entity_id,
            // {rotulo, url, excluido}: presente quando o controller resolveu a
            // entidade no banco atual (EntidadeDoHistorico).
            'entidade'    => $this->resource->getAttribute('entidade_resolvida'),
            'obs'         => $this->obs,
            'payload'     => $this->payload,
            'user'        => $this->whenLoaded('user', fn () => [
                'id'   => $this->user?->id,
                'name' => $this->user?->name,
            ]),
        ];
    }
}
