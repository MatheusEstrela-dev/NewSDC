<?php

declare(strict_types=1);

namespace App\Modules\Rat\Http\Resources;

use App\Modules\Rat\Enums\Status;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource completo para visualização/edição/impressão detalhada de um RAT.
 */
class RatResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'             => $this->id,
            'protocolo'      => $this->protocolo,
            'status'         => $this->status,
            'status_label'   => Status::tryFrom($this->status)?->getLabel() ?? $this->status,
            'tem_vistoria'   => $this->tem_vistoria,
            'dados_gerais'   => $this->dados_gerais  ?? [],
            'local'          => $this->local          ?? [],
            'endereco'       => $this->endereco        ?? [],
            'comunicacao'    => $this->comunicacao     ?? [],
            'recursos'       => $this->recursos        ?? [],
            'envolvidos'     => $this->envolvidos      ?? [],
            'vistoria'       => $this->vistoria        ?? [],
            'historico'      => $this->historico       ?? [],
            'imagens'        => $this->whenLoaded('imagens', fn() => $this->imagens->map(fn($img) => [
                'id'      => $img->id,
                'nome'    => $img->nome,
                'tipo'    => $img->tipo,
                'tamanho' => $img->tamanho,
                'url'     => $img->url,
            ])->values()->all(), []),
            'orgao_emissor'  => $this->orgaoEmissor,
            'criado_por'     => $this->creator?->name  ?? 'Sistema',
            'atualizado_por' => $this->updater?->name,
            'created_at'     => $this->created_at?->toIso8601String(),
            'updated_at'     => $this->updated_at?->toIso8601String(),
        ];
    }
}

