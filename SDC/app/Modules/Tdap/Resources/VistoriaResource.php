<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Resources;

use App\Modules\Tdap\Models\Vistoria;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Vistoria
 */
class VistoriaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $base = [
            'id'              => $this->id,
            'nome'            => $this->nome,
            'edital'          => $this->edital,
            'lote'            => $this->lote,
            'placa_id'        => $this->placa_id,
            'modelo'          => $this->modelo,
            'cor'             => $this->cor,
            'data'            => $this->data?->toDateString(),
            'ano'             => $this->ano,
            'capacidade'      => (float) $this->capacidade,
            'parecer'         => $this->parecer?->value,
            'parecer_label'   => $this->parecer?->label(),
            'esta_vigente'    => (bool) $this->esta_vigente,
            'ficha'           => $this->ficha,
            'observacoes'     => $this->observacoes,
            'caminhao'        => $this->whenLoaded('caminhao', fn () => [
                'id'             => $this->caminhao->id,
                'placa'          => $this->caminhao->placa,
                'marca'          => $this->caminhao->marca,
                'modelo'         => $this->caminhao->modelo,
                'cor'            => $this->caminhao->cor,
                'ano'            => $this->caminhao->ano,
                'capacidade_m3'  => (float) ($this->caminhao->capacidade_m3 ?? 0),
                'prestador'      => $this->caminhao->prestador ? [
                    'id'   => $this->caminhao->prestador->id,
                    'nome' => $this->caminhao->prestador->nome,
                    'cnpj' => $this->caminhao->prestador->cnpj,
                ] : null,
            ]),
            'user'            => $this->whenLoaded('user', fn () => [
                'id'   => $this->user?->id,
                'name' => $this->user?->name,
            ]),
            'created_at'      => $this->created_at?->toIso8601String(),
            'updated_at'      => $this->updated_at?->toIso8601String(),
        ];

        // Embute todos os campos boolean + obs no payload (frontend consome direto)
        foreach (array_merge(Vistoria::ITENS_ESTRUTURAIS, Vistoria::ITENS_TANQUE) as $campo) {
            $base[$campo] = (bool) ($this->{$campo} ?? false);
            $base["{$campo}_obs"] = $this->{"{$campo}_obs"};
        }

        return $base;
    }
}
