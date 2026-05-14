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
        $hoje = now()->startOfDay();
        $inicio = $this->dt_inicio;
        $final = $this->dt_final;
        $vigente = $this->ativo && $inicio && $final && $inicio->lte($hoje) && $final->gte($hoje);

        return [
            'id'          => $this->id,
            'numero'      => $this->numero,
            'dt_inicio'   => $inicio?->toDateString(),
            'dt_final'    => $final?->toDateString(),
            'ativo'       => (bool) $this->ativo,
            'vigente'     => (bool) $vigente,
            'lotes_count' => (int) ($this->lotes_count ?? 0),
        ];
    }
}
