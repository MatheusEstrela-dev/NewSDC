<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\Tdap\Models\Lote
 */
class LoteIndexResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'numero'         => $this->numero,
            'nome'           => $this->nome,
            'contrato'       => $this->contrato,
            'ata_id'         => $this->ata_id,
            'ata_numero'     => $this->whenLoaded('ata', fn () => $this->ata->numero),
            'municipio_id'   => $this->municipio_id,
            'municipio_nome' => $this->whenLoaded('municipio', fn () => $this->municipio->nome),
            'municipio_uf'   => $this->whenLoaded('municipio', fn () => $this->municipio->uf),
            'prestador_id'   => $this->prestador_id,
            'prestador_nome' => $this->whenLoaded('prestador', fn () => $this->prestador->nome),
            'prestador_cnpj' => $this->whenLoaded('prestador', fn () => $this->prestador->cnpj),
            'qtd_agua_m3'    => (float) $this->qtd_agua_m3,
            'valor_m3'       => (float) $this->valor_m3,
            'valor_total'    => (float) $this->valor_total,
            'ativo'          => (bool) $this->ativo,
        ];
    }
}
