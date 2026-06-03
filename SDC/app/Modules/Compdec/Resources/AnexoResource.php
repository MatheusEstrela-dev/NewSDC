<?php

declare(strict_types=1);

namespace App\Modules\Compdec\Resources;

use App\Modules\Compdec\Models\CompdecAnexo;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read CompdecAnexo $resource
 *
 * @mixin CompdecAnexo
 */
class AnexoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'orgao_id' => $this->orgao_id,
            'tipo' => $this->tipo?->value,
            'tipo_label' => $this->tipo?->label(),
            'titulo' => $this->titulo,
            'descricao' => $this->descricao,
            'numero' => $this->numero,
            'data_emissao' => $this->data_emissao?->toDateString(),
            'data_validade' => $this->data_validade?->toDateString(),
            'status_validade' => $this->status_validade->value,
            'status_validade_label' => $this->status_validade->label(),
            'arquivo_url' => $this->getFirstMediaUrl(CompdecAnexo::MEDIA_ARQUIVO) ?: null,
            'arquivo_nome' => $this->getFirstMedia(CompdecAnexo::MEDIA_ARQUIVO)?->name,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
            'legacy_id' => $this->legacy_id,
        ];
    }
}
