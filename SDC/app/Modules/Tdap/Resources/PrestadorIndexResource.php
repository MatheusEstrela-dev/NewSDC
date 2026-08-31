<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Resources;

use App\Modules\Tdap\Support\Documento;
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
            'cnpj_formatado'  => Documento::cnpj($this->cnpj),
            'nome'            => $this->nome,
            'representante'   => $this->representante,
            'email'           => $this->email,
            'tel1_formatado'  => Documento::telefone($this->tel1),
            'cidade'          => $this->cidade,
            'uf'              => $this->uf,
            'ativo'           => (bool) $this->ativo,
            'caminhoes_count' => (int) ($this->caminhoes_count ?? 0),
        ];
    }
}
