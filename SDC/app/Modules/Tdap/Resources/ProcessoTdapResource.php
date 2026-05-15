<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\Tdap\Models\ProcessoTdap
 */
class ProcessoTdapResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $transicoesPermitidas = $this->estado
            ? array_map(
                fn ($e) => ['value' => $e->value, 'label' => $e->label()],
                $this->estado->transicoesPermitidas(),
            )
            : [];

        return [
            'id'             => $this->id,
            'numero'         => $this->numero,
            'estado'         => $this->estado?->value,
            'estado_label'   => $this->estado?->label(),
            'swimlane_atual' => $this->swimlane_atual?->value,
            'swimlane_label' => $this->swimlane_atual?->label(),
            'swimlane_cor'   => $this->swimlane_atual?->cor(),

            'municipio_id'   => $this->municipio_id,
            'municipio'      => $this->whenLoaded('municipio', fn () => [
                'id'   => $this->municipio?->id,
                'nome' => $this->municipio?->nome,
                'uf'   => $this->municipio?->uf,
            ]),

            'decretacao_id'  => $this->decretacao_id,
            'pae_form_id'    => $this->pae_form_id,
            'contexto'       => $this->contexto,

            'aberto_em'      => $this->aberto_em?->toIso8601String(),
            'encerrado_em'   => $this->encerrado_em?->toIso8601String(),
            'aberto_por'     => $this->whenLoaded('abertoPor', fn () => [
                'id'   => $this->abertoPor?->id,
                'name' => $this->abertoPor?->name,
            ]),

            'transicoes_permitidas' => $transicoesPermitidas,
            'eh_terminal'    => (bool) $this->isTerminal(),

            'created_at'     => $this->created_at?->toIso8601String(),
            'updated_at'     => $this->updated_at?->toIso8601String(),
        ];
    }
}
