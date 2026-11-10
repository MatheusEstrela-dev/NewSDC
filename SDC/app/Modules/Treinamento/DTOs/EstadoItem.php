<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\DTOs;

use App\Modules\Treinamento\Enums\StatusEntrega;
use App\Modules\Treinamento\Enums\StatusProgresso;
use App\Modules\Treinamento\Models\ModuloItem;

final readonly class EstadoItem
{
    public function __construct(
        public ModuloItem $item,
        public bool $liberado,
        public ?StatusProgresso $status,
        public int $posicao,
        public ?StatusEntrega $entrega,
        public ?string $parecer,
        public ?array $video = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->item->id,
            'tipo' => $this->item->tipo->value,
            'titulo' => $this->item->titulo,
            'descricao' => $this->item->descricao,
            'obrigatorio' => $this->item->obrigatorio,
            'total_paginas' => $this->item->total_paginas,
            'tem_arquivo' => $this->item->temArquivo(),
            'liberado' => $this->liberado,
            'status' => $this->status?->value,
            'posicao' => $this->posicao,
            'entrega' => $this->entrega?->value,
            'entrega_label' => $this->entrega?->getLabel(),
            'parecer' => $this->parecer,
            'video' => $this->video,
        ];
    }
}
