<?php

declare(strict_types=1);

namespace App\Modules\Rat\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Serializacao leve do RAT legado para a listagem (arquivo morto).
 *
 * @mixin \App\Modules\Rat\Models\LegadoRat
 */
class LegadoRatListResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'num_ocorrencia' => $this->num_ocorrencia,
            'dt_ocorrencia' => $this->dt_ocorrencia?->toIso8601String(),
            'municipio' => $this->municipio_nome ?? $this->cedec_nome ?? 'Nao informado',
            'tipo' => $this->tipo_descricao ?? 'Nao informado',
            'tipo_codigo' => $this->tipo_codigo,
            'cobrade' => $this->cobrade_nome,
            'operador' => $this->operador_nome ?: 'Nao informado',
            'nome_operacao' => $this->nome_operacao,
        ];
    }
}
