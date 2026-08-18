<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\Cisterna\Models\CisternaVistoria
 */
class VistoriaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'beneficiario_id' => $this->beneficiario_id,
            'etapa' => [
                'valor' => $this->etapa->value,
                'rotulo' => $this->etapa->label(),
            ],
            'numero_instalacao' => $this->numero_instalacao,
            'concluida' => $this->estaConcluida(),
            'concluida_em' => $this->concluida_em?->toIso8601String(),

            'engenheiro' => [
                'nome' => $this->engenheiro_nome,
                'crea' => $this->engenheiro_crea,
                'art' => $this->engenheiro_art,
            ],
            'data_relatorio' => $this->data_relatorio?->toDateString(),
            'local_relatorio' => $this->local_relatorio,

            // Somente na etapa CEDEC.
            'dados_administrativos' => $this->when(
                $this->etapa->exigeDadosAdministrativos(),
                fn (): array => [
                    'processo_sei' => $this->processo_sei,
                    'contrato' => $this->contrato,
                    'empenho' => $this->empenho,
                    'placa_obras' => $this->placa_obras,
                ]
            ),

            'local' => [
                'endereco' => $this->endereco,
                'bairro' => $this->bairro,
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
            ],

            'itens' => $this->whenLoaded(
                'itensConferidos',
                fn (): array => $this->itensConferidos->map(fn ($i): array => [
                    'item' => $i->item->value,
                    'rotulo' => $i->item->label(),
                    'conferido' => $i->conferido,
                    'quantidade' => $i->quantidade,
                    'unidade' => $i->unidade?->value,
                    'detalhes' => $i->detalhes,
                    'observacao' => $i->observacao,
                ])->all()
            ),

            'observacoes' => $this->observacoes,
            'criado_em' => $this->created_at?->toIso8601String(),
        ];
    }
}
