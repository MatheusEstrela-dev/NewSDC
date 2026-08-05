<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CertificadoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'inscricao_id' => $this->inscricao_id,
            'treinamento_id' => $this->treinamento_id,
            'treinamento_titulo' => $this->whenLoaded('treinamento', fn () => $this->treinamento->titulo),
            'inscrito_nome' => $this->whenLoaded('inscricao', fn () => $this->inscricao->inscrito?->name),
            'status' => $this->status->value,
            'status_label' => $this->status->getLabel(),
            'status_color' => $this->status->getBadgeColor(),
            'disponivel' => $this->status->value === 'GERADO',
            'emitido_em' => $this->emitido_em?->toIso8601String(),
        ];
    }
}
