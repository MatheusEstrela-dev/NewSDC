<?php

declare(strict_types=1);

namespace App\Modules\Rat\Http\Resources;

use App\Support\Html\LegacyHtmlSanitizer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Serializacao completa do RAT legado para a tela de detalhe/impressao.
 *
 * @mixin \App\Modules\Rat\Models\LegadoRat
 */
class LegadoRatResource extends JsonResource
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
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            'municipio' => $this->municipio_nome ?? $this->cedec_nome ?? 'Nao informado',
            'tipo' => $this->tipo_descricao ?? 'Nao informado',
            'tipo_codigo' => $this->tipo_codigo,
            'alvo' => $this->alvo_descricao ?? 'Nao informado',
            'cobrade' => $this->cobrade_nome,
            'cobrade_codigo' => $this->cobrade_codigo,

            'operador' => $this->operador_nome ?: 'Nao informado',
            'nome_operacao' => $this->nome_operacao,
            'lugar_descricao' => $this->lugar_descricao,
            'envolvidos' => $this->envolvidos,

            'endereco' => $this->endereco,
            'numero' => $this->numero,
            'bairro' => $this->bairro,
            'estado' => $this->estado,
            'cep' => $this->cep,
            'referencia' => $this->referencia,

            'acoes_html' => LegacyHtmlSanitizer::clean($this->acoes),
        ];
    }
}
