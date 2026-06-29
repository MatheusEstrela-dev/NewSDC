<?php

declare(strict_types=1);

namespace App\Modules\Pmda\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ComunidadeResource extends JsonResource
{
    /** Consumo de referencia por pessoa/dia para o calculo da demanda. */
    private const LITROS_POR_PESSOA_DIA = 20;

    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'pmda_plano_id'       => $this->pmda_plano_id,
            'comunidade_id'       => $this->comunidade_id,
            'municipio_id'        => $this->municipio_id,
            'ponto_id'            => $this->ponto_id,
            'nome'                => $this->nome,
            'latitude'            => $this->latitude,
            'longitude'           => $this->longitude,
            'trecho_pav'          => $this->trecho_pav,
            'trecho_n_pav'        => $this->trecho_n_pav,
            'distancia_km'        => $this->distancia_km,
            'pop_atendida'        => $this->pop_atendida,
            // Demanda estimada (Etapa 5): populacao x 20 L/dia.
            'demanda_litros'      => (int) ($this->pop_atendida ?? 0) * self::LITROS_POR_PESSOA_DIA,
            'representantes_count' => $this->whenCounted('representantes'),
            'representantes'      => RepresentanteResource::collection($this->whenLoaded('representantes')),
        ];
    }
}
