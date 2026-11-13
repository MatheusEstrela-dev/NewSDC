<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Resources;

use App\Modules\Treinamento\Models\Treinamento;
use Illuminate\Http\Request;

/** Lista do catalogo do portal: a do SDC mais a capa servida pela rota do cidadao. */
class PortalTreinamentoListResource extends TreinamentoListResource
{
    public function toArray(Request $request): array
    {
        return parent::toArray($request) + [
            'capa_url' => $this->isEad() ? $this->capaUrl(Treinamento::ROTA_CAPA_PORTAL) : null,
        ];
    }
}
