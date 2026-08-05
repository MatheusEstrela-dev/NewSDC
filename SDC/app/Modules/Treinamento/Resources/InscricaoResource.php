<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InscricaoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'treinamento_id' => $this->treinamento_id,
            'treinamento_titulo' => $this->whenLoaded('treinamento', fn () => $this->treinamento->titulo),
            'treinamento_data_inicio' => $this->whenLoaded('treinamento', fn () => $this->treinamento->data_inicio?->toDateString()),
            'treinamento_local' => $this->whenLoaded('treinamento', fn () => $this->treinamento->local),
            'status' => $this->status->value,
            'status_label' => $this->status->getLabel(),
            'status_color' => $this->status->getBadgeColor(),
            'qr_code_token' => $this->qr_code_token,
            'data_inscricao' => $this->data_inscricao?->toIso8601String(),
            'data_aprovacao' => $this->data_aprovacao?->toIso8601String(),
            'observacoes' => $this->observacoes,
            'inscrito_tipo' => $this->inscrito_type === User::class ? 'servidor' : 'cidadao',
            'inscrito_nome' => $this->whenLoaded('inscrito', fn () => $this->inscrito?->name),
            'inscrito_email' => $this->whenLoaded('inscrito', fn () => $this->inscrito?->email),
            'inscrito_cpf' => $this->whenLoaded('inscrito', fn () => $this->inscrito?->cpf),
            'percentual_frequencia' => $this->calcularPercentualFrequencia(),
            'aprovado_por_frequencia' => $this->estaAprovadoPorFrequencia(),
            'certificado' => $this->whenLoaded('certificado', fn () => $this->certificado ? new CertificadoResource($this->certificado) : null),
        ];
    }
}
