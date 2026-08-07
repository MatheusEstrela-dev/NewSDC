<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Resources;

use App\Modules\AjudaHumanitaria\Models\PedidoAh;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Versao enxuta para a listagem de pedidos.
 *
 * @property-read PedidoAh $resource
 *
 * @mixin PedidoAh
 */
class PedidoAhIndexResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'identificador' => $this->identificador,
            'numero'        => $this->numero,
            'ano'           => $this->ano,
            'pop_atendida'  => $this->pop_atendida,
            'status'        => $this->status?->value,
            'status_label'  => $this->status?->label(),
            'status_cor'    => $this->status?->cor(),
            'fase'          => $this->status?->fase()->value,
            'municipio'     => $this->whenLoaded('municipio', fn (): ?array => $this->municipio ? [
                'id'   => $this->municipio->id,
                'nome' => $this->municipio->nome ?? null,
                'uf'   => $this->municipio->uf ?? null,
            ] : null),
            'numero_decreto'       => $this->numero_decreto,
            'data_entrada_sistema' => $this->data_entrada_sistema?->toDateString(),
            'data_hora_envio'      => $this->data_hora_envio?->toDateString(),
        ];
    }
}
