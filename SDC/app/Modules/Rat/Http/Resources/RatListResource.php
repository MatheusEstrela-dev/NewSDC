<?php

declare(strict_types=1);

namespace App\Modules\Rat\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Recurso leve para a listagem de RATs.
 * Retorna apenas os campos necessários para a tabela/grid da tela de índice.
 * NÃO acessa relatos de recursos, envolvidos ou vistoria — evita N+1.
 */
class RatListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var \App\Modules\Rat\Models\RatOcorrencia $this */

        // $this->dados_gerais usa o accessor que verifica relationLoaded('relatosMorph')
        // quando eager-loaded no controller, não dispara query adicional.
        $dg = $this->dados_gerais ?? [];

        return [
            'id'           => $this->id,
            'numero_bos'   => $this->numero_bos,
            'protocolo'    => $this->protocolo ?? $this->numero_bos,
            'sequencial_ano' => $this->sequencial_ano,
            'status'       => match ((int) $this->status) {
                1       => 'finalizado',
                default => 'em_andamento',
            },
            'status_label' => match ((int) $this->status) {
                1       => 'Finalizado',
                default => 'Em Andamento',
            },
            'municipio'    => $dg['local_municipio_nome'] ?? $dg['local_municipio'] ?? null,
            'local'        => [
                'municipio'      => $dg['local_municipio_nome'] ?? $dg['local_municipio'] ?? null,
                'municipio_nome' => $dg['local_municipio_nome'] ?? null,
                'uf'             => $dg['local_estadouf'] ?? null,
            ],
            'criado_por'      => $this->creator?->name ?? 'Sistema',
            'pode_relacionar' => $this->status === 1,
            'created_at'      => $this->created_at?->toIso8601String(),
            'updated_at'      => $this->updated_at?->toIso8601String(),
        ];
    }
}
