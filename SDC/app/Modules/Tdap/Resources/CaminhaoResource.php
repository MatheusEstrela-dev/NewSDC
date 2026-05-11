<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\Tdap\Models\Caminhao
 */
class CaminhaoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'prestador_id'  => $this->prestador_id,
            'prestador'     => $this->whenLoaded('prestador', fn () => [
                'id'    => $this->prestador->id,
                'nome'  => $this->prestador->nome,
                'cnpj'  => $this->prestador->cnpj,
                'email' => $this->prestador->email ?? null,
            ]),
            'placa'         => $this->placa,
            'marca'         => $this->marca,
            'modelo'        => $this->modelo,
            'cor'           => $this->cor,
            'ano'           => $this->ano,
            'capacidade_m3' => (float) $this->capacidade_m3,
            'ativo'         => (bool) $this->ativo,
            'observacoes'   => $this->observacoes,
            'created_at'    => $this->created_at?->toIso8601String(),
            'updated_at'    => $this->updated_at?->toIso8601String(),
        ];
    }
}
