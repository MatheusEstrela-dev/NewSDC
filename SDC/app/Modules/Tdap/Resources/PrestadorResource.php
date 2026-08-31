<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Resources;

use App\Modules\Tdap\Support\Documento;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\Tdap\Models\Prestador
 */
class PrestadorResource extends JsonResource
{
    /**
     * Expoe cada documento em duas chaves: a crua (`cnpj`, `tel1`...) para o
     * formulario, que trabalha em digitos, e a `*_formatado` para leitura.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'cnpj'           => $this->cnpj,
            'cnpj_formatado' => Documento::cnpj($this->cnpj),
            'nome'           => $this->nome,
            'representante'  => $this->representante,
            'email'          => $this->email,
            'tel1'           => $this->tel1,
            'tel1_formatado' => Documento::telefone($this->tel1),
            'tel2'           => $this->tel2,
            'tel2_formatado' => Documento::telefone($this->tel2),
            'endereco'       => $this->endereco,
            'bairro'         => $this->bairro,
            'cidade'         => $this->cidade,
            'uf'             => $this->uf,
            'cep'            => $this->cep,
            'cep_formatado'  => Documento::cep($this->cep),
            'ativo'          => (bool) $this->ativo,
            'observacoes'    => $this->observacoes,
            'caminhoes_count' => $this->whenCounted('caminhoes'),
            'caminhoes'      => $this->whenLoaded('caminhoes', fn () => $this->caminhoes->map(fn ($c) => [
                'id'            => $c->id,
                'placa'         => $c->placa,
                'marca_modelo'  => trim(($c->marca ?? '').' '.($c->modelo ?? '')) ?: null,
                'capacidade_m3' => (float) $c->capacidade_m3,
                'ativo'         => (bool) $c->ativo,
            ])),
            'created_at'     => $this->created_at?->toIso8601String(),
            'updated_at'     => $this->updated_at?->toIso8601String(),
        ];
    }
}
