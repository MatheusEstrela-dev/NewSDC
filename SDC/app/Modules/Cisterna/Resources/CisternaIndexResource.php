<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Resources;

use App\Modules\Cisterna\Models\Cisterna;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Versao enxuta para listagem (Index).
 *
 * @property-read Cisterna $resource
 *
 * @mixin Cisterna
 */
class CisternaIndexResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'codigo' => $this->codigo,
            'nome' => $this->nome,
            'tipo' => $this->tipo?->value,
            'tipo_label' => $this->tipo?->label(),
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'municipio' => $this->whenLoaded('municipio', fn (): ?array => $this->municipio ? [
                'id' => $this->municipio->id,
                'nome' => $this->municipio->nome ?? null,
                'uf' => $this->municipio->uf ?? null,
            ] : null),
            'capacidade_litros' => $this->capacidade_litros,
            'data_instalacao' => $this->data_instalacao?->toDateString(),
        ];
    }
}
