<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TreinamentoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'titulo' => $this->titulo,
            'descricao' => $this->descricao,
            'carga_horaria' => $this->carga_horaria,
            'categoria' => $this->categoria->value,
            'categoria_label' => $this->categoria->getLabel(),
            'tipo' => $this->tipo->value,
            'tipo_label' => $this->tipo->getLabel(),
            'tipo_color' => $this->tipo->getBadgeColor(),
            'status' => $this->status->value,
            'status_label' => $this->status->getLabel(),
            'status_color' => $this->status->getBadgeColor(),
            'status_transicoes' => array_map(fn ($s) => $s->value, $this->status->getAllowedTransitions()),
            'instrutor' => $this->instrutor,
            'local' => $this->local,
            'data_inicio' => $this->data_inicio?->toDateString(),
            'data_fim' => $this->data_fim?->toDateString(),
            'hora_inicio' => $this->hora_inicio ? substr($this->hora_inicio, 0, 5) : null,
            'numero_vagas' => $this->numero_vagas,
            'vagas_disponiveis' => $this->vagas_disponiveis,
            'total_inscricoes' => $this->total_inscricoes,
            'percentual_frequencia_minimo' => (float) $this->percentual_frequencia_minimo,
            'link_publico_slug' => $this->link_publico_slug,
            'esta_publicado' => $this->estaPublicado(),
            'presenca_liberada' => (bool) $this->presenca_liberada,
            'presenca_autoconfirmavel' => (bool) $this->presenca_autoconfirmavel,
            'finalizado_em' => $this->finalizado_em?->toIso8601String(),
            'pode_receber_inscricao' => $this->podeReceberInscricao(),
            'pode_registrar_frequencia' => $this->status->podeRegistrarFrequencia(),
            'modulos' => $this->whenLoaded('modulos', fn () => $this->modulos->map(fn ($modulo) => [
                'id' => $modulo->id,
                'titulo' => $modulo->titulo,
                'descricao' => $modulo->descricao,
                'ordem' => $modulo->ordem,
                'carga_horaria' => $modulo->carga_horaria,
                'data_prevista' => $modulo->data_prevista?->toDateString(),
            ])),
            'inscricoes' => InscricaoResource::collection($this->whenLoaded('inscricoes')),
        ];
    }
}
