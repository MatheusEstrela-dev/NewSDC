<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Resources;

use App\Modules\Cisterna\Models\Cisterna;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read Cisterna $resource
 *
 * @mixin Cisterna
 */
class CisternaResource extends JsonResource
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
            'municipio_id' => $this->municipio_id,
            'municipio' => $this->whenLoaded('municipio', fn (): ?array => $this->municipio ? [
                'id' => $this->municipio->id,
                'nome' => $this->municipio->nome ?? null,
                'uf' => $this->municipio->uf ?? null,
            ] : null),
            'endereco' => $this->endereco,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'capacidade_litros' => $this->capacidade_litros,
            'data_instalacao' => $this->data_instalacao?->toDateString(),
            'responsavel_nome' => $this->responsavel_nome,
            'responsavel_telefone' => $this->responsavel_telefone,
            'observacoes' => $this->observacoes,
            'legacy_id' => $this->legacy_id,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
        ];
    }
}
