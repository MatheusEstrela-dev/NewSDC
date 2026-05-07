<?php

declare(strict_types=1);

namespace App\Modules\Compdec\Resources;

use App\Modules\Compdec\Models\Prefeitura;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read Prefeitura $resource
 *
 * @mixin Prefeitura
 */
class PrefeituraResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'municipio_id' => $this->municipio_id,
            'prefeito_nome' => $this->prefeito_nome,
            'prefeito_telefone' => $this->prefeito_telefone,
            'prefeito_celular' => $this->prefeito_celular,
            'prefeito_email' => $this->prefeito_email,
            'endereco' => $this->endereco,
            'bairro' => $this->bairro,
            'cep' => $this->cep,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'inss_tem_cobranca' => $this->inss_tem_cobranca,
            'inss_aliquota' => $this->inss_aliquota,
            'inss_lei_cobranca' => $this->inss_lei_cobranca,
            'inss_responsavel' => $this->inss_responsavel,
            'foto_url' => $this->getFirstMediaUrl(Prefeitura::MEDIA_FOTO_PREFEITO) ?: null,
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
