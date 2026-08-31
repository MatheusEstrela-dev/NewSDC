<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Resources;

use App\Modules\Tdap\Models\Vistoria;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Vistoria
 */
class VistoriaIndexResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'nome'            => $this->nome,
            'edital'          => $this->edital,
            'data'            => $this->data?->toDateString(),
            'parecer'         => $this->parecer?->value,
            'parecer_label'   => $this->parecer?->label(),
            'esta_vigente'    => (bool) $this->esta_vigente,
            'ficha'           => $this->ficha,
            // Numero do lacre: a grade mostra no lugar do antigo "lote", que
            // repetia o lote da ata sem valor para a conferencia da vistoria.
            'lacre'           => $this->lacre,
            'caminhao_placa'  => $this->whenLoaded('caminhao', fn () => $this->caminhao?->placa),
            'caminhao_modelo' => $this->whenLoaded('caminhao', fn () => trim(($this->caminhao?->marca ?? '').' '.($this->caminhao?->modelo ?? '')) ?: null),
            'prestador_nome'  => $this->whenLoaded('caminhao', fn () => $this->caminhao?->prestador?->nome),
        ];
    }
}
