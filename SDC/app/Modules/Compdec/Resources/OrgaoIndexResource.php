<?php

declare(strict_types=1);

namespace App\Modules\Compdec\Resources;

use App\Modules\Compdec\Models\Orgao;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Versao enxuta do Orgao para listagem (Index).
 *
 * @property-read Orgao $resource
 *
 * @mixin Orgao
 */
class OrgaoIndexResource extends JsonResource
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
            'usuarios_count' => $this->when($this->usuarios_count !== null, $this->usuarios_count),
            'equipes_count' => $this->when($this->equipes_count !== null, $this->equipes_count),
            'qtd_efetivo' => $this->qtd_efetivo,
            'tem_plano_contingencia' => $this->tem_plano_contingencia,
        ];
    }
}
