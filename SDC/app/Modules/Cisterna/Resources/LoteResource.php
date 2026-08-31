<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\Cisterna\Models\CisternaLote
 */
class LoteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'data' => $this->data?->toDateString(),
            'observacao' => $this->observacao,
            'ordens_servico' => $this->when(
                $this->ordens_servico_count !== null,
                fn (): int => (int) $this->ordens_servico_count
            ),
        ];
    }
}
