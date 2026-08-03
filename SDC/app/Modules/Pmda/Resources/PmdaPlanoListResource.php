<?php

declare(strict_types=1);

namespace App\Modules\Pmda\Resources;

use App\Modules\Pmda\Enums\PmdaStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PmdaPlanoListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'protocolo'    => $this->protocolo,
            'municipio'    => $this->whenLoaded('municipio', fn () => $this->municipio->nome ?? null),
            'municipio_id' => $this->municipio_id,
            'status'       => $this->status->value,
            'status_label' => $this->status->getLabel(),
            // Nome de cor da paleta do Badge; o componente aplica a receita de pill.
            'status_cor'   => $this->status->getCor(),
            // @deprecated Classe Tailwind crua. Sai quando nenhum consumidor usar.
            'status_color' => $this->status->getColorClass(),
            'data'         => $this->data?->toIso8601String(),
            'dt_analise'   => $this->dt_analise?->toIso8601String(),
            'resp_homolog' => $this->resp_homolog,
            'pode_copiar'  => $this->status->permiteCopia(),
            // Exclusao: admin/super-admin em qualquer status; demais so ATENDIDO.
            'pode_excluir' => ($request->user()?->hasAnyRole('super-admin', 'admin') ?? false)
                || $this->status === PmdaStatus::ATENDIDO,
        ];
    }
}
