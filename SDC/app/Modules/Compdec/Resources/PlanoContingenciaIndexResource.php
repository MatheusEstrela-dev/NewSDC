<?php

declare(strict_types=1);

namespace App\Modules\Compdec\Resources;

use App\Modules\Compdec\Models\CompdecPlanoContingencia;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Versao enxuta para tab Plano de Contingencia (lista no show do orgao).
 *
 * @property-read CompdecPlanoContingencia $resource
 *
 * @mixin CompdecPlanoContingencia
 */
class PlanoContingenciaIndexResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'versao' => $this->versao,
            'ativo' => $this->ativo,
            'esta_aprovado' => $this->esta_aprovado,
            'aprovado_em' => $this->aprovado_em?->toIso8601String(),
            'aprovador_nome' => $this->whenLoaded('aprovador', fn (): ?string => $this->aprovador?->name),
            'tamanho_bytes' => $this->tamanho_bytes,
            'tem_arquivo' => $this->getFirstMedia(CompdecPlanoContingencia::MEDIA_ARQUIVO) !== null,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
