<?php

declare(strict_types=1);

namespace App\Modules\Pmda\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PmdaPlanoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'protocolo'         => $this->protocolo,
            'municipio_id'      => $this->municipio_id,
            'municipio'         => $this->whenLoaded('municipio', fn () => $this->municipio->nome ?? null),
            'status'            => $this->status->value,
            'status_label'      => $this->status->getLabel(),
            'status_color'      => $this->status->getColorClass(),
            'pode_copiar'       => $this->status->permiteCopia(),
            'data'              => $this->data?->toIso8601String(),
            'acoes'             => $this->acoes,
            'qtd_caminhao'      => $this->qtd_caminhao,
            'pop_at_municipio'  => $this->pop_at_municipio,
            'cobra_iss'         => $this->cobra_iss,
            'num_lei_iss'       => $this->num_lei_iss,
            'aliquota_iss'      => $this->aliquota_iss,
            'resp_cob_iss'      => $this->resp_cob_iss,
            'data_aprov'        => $this->data_aprov?->toIso8601String(),
            'dt_ultima_alteracao' => $this->dt_ultima_alteracao?->toIso8601String(),
        ];
    }
}
