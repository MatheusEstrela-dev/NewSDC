<?php

declare(strict_types=1);

namespace App\Modules\Rat\Http\Resources;

use App\Modules\Rat\Enums\Status;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource leve para uso na listagem de RATs.
 * Expõe apenas os campos necessários para a tabela/grade do índice.
 */
class RatListResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'           => $this->id,
            'protocolo'    => $this->protocolo,
            'status'       => $this->status,
            'status_label' => Status::tryFrom($this->status)?->getLabel() ?? $this->status,
            'local'        => $this->local,
            'data_fato'    => $this->dados_gerais['data_fato'] ?? null,
            'criado_por'   => $this->creator?->name ?? 'Sistema',
            'created_at'   => $this->created_at?->toIso8601String(),
            'updated_at'   => $this->updated_at?->toIso8601String(),
        ];
    }
}

