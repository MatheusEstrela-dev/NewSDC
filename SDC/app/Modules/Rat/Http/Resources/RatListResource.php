<?php

declare(strict_types=1);

namespace App\Modules\Rat\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RatListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var \App\Modules\Rat\Models\RatOcorrencia $this */

        $dadosGerais = $this->dados_gerais;

        return [
            'dados_gerais' => $dadosGerais ?: null,
            'recursos'     => $this->recursos ?: [],
            'envolvidos'   => $this->envolvidos ?: [],
            'vistoria'     => $this->vistoria ? ['id' => $this->vistoria['id'] ?? null] : null,
        ];
    }
}
