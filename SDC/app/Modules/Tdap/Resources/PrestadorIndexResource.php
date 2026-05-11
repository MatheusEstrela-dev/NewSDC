<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\Tdap\Models\Prestador
 */
class PrestadorIndexResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'cnpj_formatado'  => $this->formatarCnpj($this->cnpj),
            'nome'            => $this->nome,
            'representante'   => $this->representante,
            'email'           => $this->email,
            'cidade'          => $this->cidade,
            'uf'              => $this->uf,
            'ativo'           => (bool) $this->ativo,
            'caminhoes_count' => (int) ($this->caminhoes_count ?? 0),
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
