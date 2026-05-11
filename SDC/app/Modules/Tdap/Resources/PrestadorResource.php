<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\Tdap\Models\Prestador
 */
class PrestadorResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'cnpj'           => $this->cnpj,
            'cnpj_formatado' => $this->formatarCnpj($this->cnpj),
            'nome'           => $this->nome,
            'representante'  => $this->representante,
            'email'          => $this->email,
            'tel1'           => $this->tel1,
            'tel2'           => $this->tel2,
            'endereco'       => $this->endereco,
            'bairro'         => $this->bairro,
            'cidade'         => $this->cidade,
            'uf'             => $this->uf,
            'cep'            => $this->cep,
            'ativo'          => (bool) $this->ativo,
            'observacoes'    => $this->observacoes,
            'caminhoes_count' => $this->whenCounted('caminhoes'),
            'created_at'     => $this->created_at?->toIso8601String(),
            'updated_at'     => $this->updated_at?->toIso8601String(),
        ];
    }

    private function formatarCnpj(string $cnpj): string
    {
        if (strlen($cnpj) !== 14) {
            return $cnpj;
        }

        return substr($cnpj, 0, 2).'.'
            .substr($cnpj, 2, 3).'.'
            .substr($cnpj, 5, 3).'/'
            .substr($cnpj, 8, 4).'-'
            .substr($cnpj, 12, 2);
    }
}
