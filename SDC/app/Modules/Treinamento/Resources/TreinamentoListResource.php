<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TreinamentoListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'titulo' => $this->titulo,
            'categoria' => $this->categoria->value,
            'categoria_label' => $this->categoria->getLabel(),
            'tipo' => $this->tipo->value,
            'tipo_label' => $this->tipo->getLabel(),
            'tipo_color' => $this->tipo->getBadgeColor(),
            'status' => $this->status->value,
            'status_label' => $this->status->getLabel(),
            'status_color' => $this->status->getBadgeColor(),
            'instrutor' => $this->instrutor,
            'local' => $this->local,
            'data_inicio' => $this->data_inicio?->toDateString(),
            'data_fim' => $this->data_fim?->toDateString(),
            'numero_vagas' => $this->numero_vagas,
            'vagas_disponiveis' => $this->vagas_disponiveis,
            'total_inscricoes' => $this->total_inscricoes,
            'total_modulos' => $this->whenLoaded('modulos', fn () => $this->modulos->count(), 0),
            'link_publico_slug' => $this->link_publico_slug,
            'esta_publicado' => $this->estaPublicado(),
            'pode_receber_inscricao' => $this->podeReceberInscricao(),
        ];
    }
}
