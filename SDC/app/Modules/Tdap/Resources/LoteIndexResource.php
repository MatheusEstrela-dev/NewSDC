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
            // Lista completa: um lote atende varios municipios. A grade mostra
            // esta lista na coluna "Municipio" (antes vinha do municipio_id
            // unico, que na base legada era 0 e nao exibia nada).
            'municipios'     => $this->whenLoaded(
                'municipios',
                fn () => $this->municipios
                    ->map(fn ($m) => ['id' => $m->id, 'nome' => $m->nome, 'uf' => $m->uf])
                    ->values(),
            ),
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
