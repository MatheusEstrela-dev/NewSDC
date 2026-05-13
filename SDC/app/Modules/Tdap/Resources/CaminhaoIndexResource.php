<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\Tdap\Models\Caminhao
 */
class CaminhaoIndexResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'placa'          => $this->placa,
            'marca'          => $this->marca,
            'modelo'         => $this->modelo,
            'ano'            => $this->ano,
            'capacidade_m3'  => (float) $this->capacidade_m3,
            'ativo'          => (bool) $this->ativo,
            'prestador_nome' => $this->whenLoaded('prestador', fn () => $this->prestador->nome),
            'prestador_cnpj' => $this->whenLoaded('prestador', fn () => $this->prestador->cnpj),
        ];
    }
}
