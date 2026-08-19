<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Resources;

use App\Modules\Cisterna\DTOs\NotificacaoDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\Cisterna\Models\CisternaNotificacao
 */
class NotificacaoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Devolve o alias curto, nao o FQCN: o frontend nao precisa conhecer
        // a estrutura interna.
        $alias = array_search($this->notificavel_type, NotificacaoDTO::TIPOS_PERMITIDOS, true);

        return [
            'id' => $this->id,
            'notificavel' => [
                'tipo' => $alias === false ? null : $alias,
                'id' => $this->notificavel_id,
            ],
            'observacao' => $this->observacao,
            'respondida' => $this->respondida,
            'respondida_em' => $this->respondida_em?->toIso8601String(),
            'emitida_por' => $this->whenLoaded('criador', fn (): ?string => $this->criador?->name),
            'documentos' => $this->when(
                $this->relationLoaded('media'),
                fn (): array => $this->getMedia('documentos')->map(fn ($m): array => [
                    'id' => $m->id,
                    'url' => $m->getUrl(),
                    'nome' => $m->file_name,
                ])->all()
            ),
            'criado_em' => $this->created_at?->toIso8601String(),
        ];
    }
}
