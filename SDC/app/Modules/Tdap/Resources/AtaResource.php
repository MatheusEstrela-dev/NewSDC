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
        // Mesma fonte de verdade da listagem (VigenciaAta, via accessor).
        $situacao = $this->situacao;

        return [
            'id'             => $this->id,
            'numero'         => $this->numero,
            'dt_inicio'      => $this->dt_inicio?->toDateString(),
            'dt_final'       => $this->dt_final?->toDateString(),
            'historico'      => $this->historico,
            'ativo'          => (bool) $this->ativo,
            // Mantido por compatibilidade: Show/Edit ja leem `vigente`.
            'vigente'        => $situacao->isVigente(),
            'situacao'       => $situacao->value,
            'situacao_label' => $situacao->label(),
            'situacao_cor'   => $situacao->cor(),
            'dias_restantes' => $this->dias_restantes,
            'proxima_vencer' => $this->proxima_vencer,
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
