<?php

declare(strict_types=1);

namespace App\Modules\Compdec\Resources;

use App\Modules\Compdec\Models\CompdecEquipe;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Versao enxuta para tab Equipe (lista no show do orgao).
 *
 * @property-read CompdecEquipe $resource
 *
 * @mixin CompdecEquipe
 */
class EquipeIndexResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'funcao' => $this->funcao?->value,
            'funcao_label' => $this->funcao?->label(),
            'telefone' => $this->telefone,
            'celular' => $this->celular,
            'email' => $this->email,
            'ativo' => $this->ativo,
            'ordem' => $this->ordem,
        ];
    }
}
