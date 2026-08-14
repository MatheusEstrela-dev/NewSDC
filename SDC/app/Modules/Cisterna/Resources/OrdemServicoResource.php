<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\Cisterna\Models\CisternaOrdemServico
 */
class OrdemServicoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'observacao' => $this->observacao,
            'lote' => [
                'id' => $this->lote_id,
                'nome' => $this->lote?->nome,
            ],
            'beneficiarios' => $this->when(
                $this->beneficiarios_count !== null,
                fn (): int => (int) $this->beneficiarios_count
            ),
            // URL do processo no SEI, vinda do legado.
            'documento_url' => $this->documento_url,
            // Arquivo anexado no NewSDC, que o legado nao tinha.
            'documento_anexo' => $this->when(
                $this->relationLoaded('media'),
                fn (): ?string => $this->getFirstMediaUrl('documento_os') ?: null
            ),
        ];
    }
}
