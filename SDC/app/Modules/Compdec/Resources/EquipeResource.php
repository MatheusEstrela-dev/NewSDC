<?php

declare(strict_types=1);

namespace App\Modules\Compdec\Resources;

use App\Modules\Compdec\Models\CompdecEquipe;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read CompdecEquipe $resource
 *
 * @mixin CompdecEquipe
 */
class EquipeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'orgao_id' => $this->orgao_id,
            'nome' => $this->nome,
            'funcao' => $this->funcao?->value,
            'funcao_label' => $this->funcao?->label(),
            'telefone' => $this->telefone,
            'celular' => $this->celular,
            'email' => $this->email,
            'cpf' => $this->cpf,
            'ativo' => $this->ativo,
            'ordem' => $this->ordem,
            'observacoes' => $this->observacoes,
            'legacy_id' => $this->legacy_id,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
        ];
    }
}
