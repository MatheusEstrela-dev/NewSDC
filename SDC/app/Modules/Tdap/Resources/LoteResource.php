<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\Tdap\Models\Lote
 */
class LoteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'ata_id'       => $this->ata_id,
            'ata'          => $this->whenLoaded('ata', fn () => [
                'id'        => $this->ata->id,
                'numero'    => $this->ata->numero,
                'dt_inicio' => $this->ata->dt_inicio?->toDateString(),
                'dt_final'  => $this->ata->dt_final?->toDateString(),
            ]),
            // Municipios atendidos pelo lote (relacao N:N). `municipio_ids`
            // alimenta o multi-select do formulario.
            'municipios'    => $this->whenLoaded(
                'municipios',
                fn () => $this->municipios
                    ->map(fn ($m) => ['id' => $m->id, 'nome' => $m->nome, 'uf' => $m->uf])
                    ->values(),
            ),
            'municipio_ids' => $this->whenLoaded(
                'municipios',
                fn () => $this->municipios->pluck('id')->values(),
            ),
            'prestador_id' => $this->prestador_id,
            'prestador'    => $this->whenLoaded('prestador', fn () => [
                'id'    => $this->prestador->id,
                'nome'  => $this->prestador->nome,
                'cnpj'  => $this->prestador->cnpj,
                'email' => $this->prestador->email ?? null,
            ]),
            'numero'       => $this->numero,
            'nome'         => $this->nome,
            'contrato'     => $this->contrato,
            'qtd_agua_m3'  => (float) $this->qtd_agua_m3,
            'valor_m3'     => (float) $this->valor_m3,
            'valor_total'  => (float) $this->valor_total,
            'ativo'        => (bool) $this->ativo,
            'observacoes'  => $this->observacoes,
            // A tela de detalhe usa para esconder o botao Excluir quando ha
            // cronograma vinculado (o service recusaria a exclusao).
            'cronogramas_count' => $this->whenCounted('cronogramas'),
            'created_at'   => $this->created_at?->toIso8601String(),
            'updated_at'   => $this->updated_at?->toIso8601String(),
        ];
    }
}
