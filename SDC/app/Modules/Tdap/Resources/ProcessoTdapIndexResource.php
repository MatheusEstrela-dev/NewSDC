<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\Tdap\Models\ProcessoTdap
 */
class ProcessoTdapIndexResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'numero'         => $this->numero,
            'estado'         => $this->estado?->value,
            'estado_label'   => $this->estado?->label(),
            'swimlane_atual' => $this->swimlane_atual?->value,
            'swimlane_label' => $this->swimlane_atual?->label(),
            'swimlane_cor'   => $this->swimlane_atual?->cor(),
            'municipio_id'   => $this->municipio_id,
            'municipio_nome' => $this->whenLoaded('municipio', fn () => $this->municipio?->nome),
            'municipio_uf'   => $this->whenLoaded('municipio', fn () => $this->municipio?->uf),
            'aberto_em'      => $this->aberto_em?->toIso8601String(),
            'encerrado_em'   => $this->encerrado_em?->toIso8601String(),
        ];
    }
}
