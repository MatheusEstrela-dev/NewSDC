<?php

declare(strict_types=1);

namespace App\Modules\Pmda\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ComunidadeSolicitacaoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'municipio_id'    => $this->municipio_id,
            'municipio'       => $this->whenLoaded('municipio', fn () => $this->municipio->nome ?? null),
            'pmda_plano_id'   => $this->pmda_plano_id,
            'nome'            => $this->nome,
            'latitude'        => $this->latitude,
            'longitude'       => $this->longitude,
            'status'          => $this->status->value,
            'status_label'    => $this->status->getLabel(),
            // Nome de cor da paleta do Badge; o componente aplica a receita de pill.
            'status_cor'      => $this->status->getCor(),
            'comunidade_id'   => $this->comunidade_id,
            'motivo_rejeicao' => $this->motivo_rejeicao,
            'analisado_em'    => $this->analisado_em?->toIso8601String(),
            'created_at'      => $this->created_at?->toIso8601String(),
            // Detalhamento da fila CEDEC: quem pediu, de qual PMDA e quem decidiu.
            'solicitante'     => $this->whenLoaded('solicitadoPor', fn () => [
                'nome'  => $this->solicitadoPor->name,
                'email' => $this->solicitadoPor->email,
            ]),
            'plano_protocolo' => $this->whenLoaded('plano', fn () => $this->plano?->protocolo),
            'analisado_por_nome' => $this->whenLoaded('analisadoPor', fn () => $this->analisadoPor?->name),
        ];
    }
}
