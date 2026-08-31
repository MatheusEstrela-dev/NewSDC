<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\Tdap\Models\Cronograma
 */
class CronogramaIndexResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'numero'          => $this->numero,
            'estado'          => $this->estado,
            'ativo'           => (bool) $this->ativo,
            'arquivado'       => $this->arquivado_em !== null,
            'encerrado_em'    => $this->encerrado_em?->toIso8601String(),
            'dt_inicio'       => $this->dt_inicio?->toDateString(),
            'dt_final'        => $this->dt_final?->toDateString(),
            'ata_numero'      => $this->whenLoaded('ata', fn () => $this->ata?->numero),
            'lote_numero'     => $this->whenLoaded('lote', fn () => $this->lote?->numero),
            'municipio_nome'  => $this->whenLoaded('municipio', fn () => $this->municipio?->nome),
            'municipio_uf'    => $this->whenLoaded('municipio', fn () => $this->municipio?->uf),
            'prestador_nome'  => $this->whenLoaded('prestador', fn () => $this->prestador?->nome),
            'caminhoes_count' => (int) ($this->caminhoes_count ?? 0),
            'volume_contratado_m3' => $this->volume_contratado,
            'volume_entregue_m3'   => $this->volume_entregue,
            'execucao_percentual'  => $this->percentual_entregue,
        ];
    }
}
