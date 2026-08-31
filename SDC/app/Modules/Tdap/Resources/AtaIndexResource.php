<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\Tdap\Models\Ata
 */
class AtaIndexResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Regra centralizada em VigenciaAta e lida via accessor do model — antes
        // esta linha recalculava a vigencia a mao e nao enxergava ata vencida.
        $situacao = $this->situacao;

        return [
            'id'             => $this->id,
            'numero'         => $this->numero,
            'dt_inicio'      => $this->dt_inicio?->toDateString(),
            'dt_final'       => $this->dt_final?->toDateString(),
            'ativo'          => (bool) $this->ativo,
            // Mantido por compatibilidade: consumidores antigos leem `vigente`.
            'vigente'        => $situacao->isVigente(),
            'situacao'       => $situacao->value,
            'situacao_label' => $situacao->label(),
            'situacao_cor'   => $situacao->cor(),
            'dias_restantes' => $this->dias_restantes,
            'proxima_vencer' => $this->proxima_vencer,
            'lotes_count'    => (int) ($this->lotes_count ?? 0),
        ];
    }
}
