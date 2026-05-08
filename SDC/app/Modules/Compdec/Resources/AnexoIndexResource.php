<?php

declare(strict_types=1);

namespace App\Modules\Compdec\Resources;

use App\Modules\Compdec\Models\CompdecAnexo;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Versao enxuta para listagem na tab Anexos.
 *
 * @property-read CompdecAnexo $resource
 *
 * @mixin CompdecAnexo
 */
class AnexoIndexResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tipo' => $this->tipo?->value,
            'tipo_label' => $this->tipo?->label(),
            'titulo' => $this->titulo,
            'numero' => $this->numero,
            'data_emissao' => $this->data_emissao?->toDateString(),
            'data_validade' => $this->data_validade?->toDateString(),
            'status_validade' => $this->status_validade->value,
            'status_validade_label' => $this->status_validade->label(),
            'tem_arquivo' => $this->getFirstMedia(CompdecAnexo::MEDIA_ARQUIVO) !== null,
        ];
    }
}
