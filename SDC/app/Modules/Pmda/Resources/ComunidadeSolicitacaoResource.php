<?php

declare(strict_types=1);

namespace App\Modules\Pmda\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ComunidadeSolicitacaoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'municipio_id'    => $this->municipio_id,
            'municipio'       => $this->whenLoaded('municipio', fn () => $this->municipio->nome ?? null),
            'pmda_plano_id'   => $this->pmda_plano_id,
            'nome'            => $this->nome,
            'latitude'        => $this->latitude,
            'longitude'       => $this->longitude,
            'status'          => $this->status->value,
            'status_label'    => $this->status->getLabel(),
            'status_color'    => $this->status->getColorClass(),
            'comunidade_id'   => $this->comunidade_id,
            'motivo_rejeicao' => $this->motivo_rejeicao,
            'analisado_em'    => $this->analisado_em?->toIso8601String(),
            'created_at'      => $this->created_at?->toIso8601String(),
        ];
    }
}
