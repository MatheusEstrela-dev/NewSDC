<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\Tdap\Models\Ata
 */
class AtaResource extends JsonResource
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
            'id'             => $this->id,
            'numero'         => $this->numero,
            'dt_inicio'      => $inicio?->toDateString(),
            'dt_final'       => $final?->toDateString(),
            'historico'      => $this->historico,
            'ativo'          => (bool) $this->ativo,
            'vigente'        => (bool) $vigente,
            'observacoes'    => $this->observacoes,
            'lotes_count'    => $this->whenCounted('lotes'),
            'lotes'          => $this->whenLoaded('lotes', fn () => $this->lotes->map(fn ($l) => [
                'id'             => $l->id,
                'numero'         => $l->numero,
                'nome'           => $l->nome,
                'municipio_id'   => $l->municipio_id,
                'municipio_nome' => $l->municipio?->nome,
                'municipio_uf'   => $l->municipio?->uf,
                'prestador_id'   => $l->prestador_id,
                'prestador_nome' => $l->prestador?->nome,
                'qtd_agua_m3'    => (float) $l->qtd_agua_m3,
                'valor_m3'       => (float) $l->valor_m3,
                'ativo'          => (bool) $l->ativo,
            ])),
            'created_at'     => $this->created_at?->toIso8601String(),
            'updated_at'     => $this->updated_at?->toIso8601String(),
        ];
    }
}
