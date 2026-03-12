<?php

declare(strict_types=1);

namespace App\Modules\Rat\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource completo de uma ocorrência RAT (rat_ocorrencias).
 */
class RatResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                   => $this->id,
            'numero_bos'           => $this->numero_bos,
            'status'               => $this->status,
            'status_label'         => $this->status_label,
            'historico'            => $this->historico,
            'prazo_edicao'         => $this->prazo_edicao?->toIso8601String(),
            'sequencial_ano'       => $this->sequencial_ano,
            'ocorrencia_origem_id' => $this->ocorrencia_origem_id,
            'created_by'           => $this->created_by,
            'updated_by'           => $this->updated_by,
            'created_at'           => $this->created_at?->toIso8601String(),
            'updated_at'           => $this->updated_at?->toIso8601String(),
        ];
    }
}
