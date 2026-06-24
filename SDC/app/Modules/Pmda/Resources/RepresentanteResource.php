<?php

declare(strict_types=1);

namespace App\Modules\Pmda\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RepresentanteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'pmda_comunidade_id' => $this->pmda_comunidade_id,
            'nome'               => $this->nome,
            'tel'                => $this->tel,
            'email'              => $this->email,
            'cpf'                => $this->cpf,
            'whatsapp'           => $this->whatsapp,
        ];
    }
}
