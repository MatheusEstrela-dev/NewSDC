<?php

declare(strict_types=1);

namespace App\Modules\Pmda\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PmdaPlanoListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'protocolo'    => $this->protocolo,
            'municipio'    => $this->whenLoaded('municipio', fn () => $this->municipio->nome ?? null),
            'municipio_id' => $this->municipio_id,
            'status'       => $this->status->value,
            'status_label' => $this->status->getLabel(),
            'status_color' => $this->status->getColorClass(),
            'data'         => $this->data?->toIso8601String(),
            'pode_copiar'  => $this->status->permiteCopia(),
        ];
    }
}
