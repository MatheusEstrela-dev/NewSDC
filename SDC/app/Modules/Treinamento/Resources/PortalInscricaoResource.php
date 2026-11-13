<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Resources;

use App\Modules\Treinamento\Models\Treinamento;
use Illuminate\Http\Request;

/** Inscricoes do portal: a do SDC mais a capa do curso servida pela rota do cidadao. */
class PortalInscricaoResource extends InscricaoResource
{
    public function toArray(Request $request): array
    {
        return parent::toArray($request) + [
            'treinamento_capa_url' => $this->whenLoaded('treinamento', fn () => $this->treinamento->isEad()
                ? $this->treinamento->capaUrl(Treinamento::ROTA_CAPA_PORTAL)
                : null),
        ];
    }
}
