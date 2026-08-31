<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\Cisterna\Models\CisternaComunidade
 */
class ComunidadeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'ativa' => $this->ativa,
            'municipio' => [
                'id' => $this->municipio_id,
                'nome' => $this->municipio?->nome,
                'uf' => $this->municipio?->uf,
            ],
            // Contado por comunidade_id, nao pelo nome: no legado comunidades
            // homonimas de municipios diferentes somavam a contagem.
            'beneficiarios' => $this->when(
                $this->beneficiarios_count !== null,
                fn (): int => (int) $this->beneficiarios_count
            ),
        ];
    }
}
