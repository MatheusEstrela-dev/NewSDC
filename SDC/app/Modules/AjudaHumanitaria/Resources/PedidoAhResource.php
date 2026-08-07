<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Resources;

use App\Modules\AjudaHumanitaria\Models\PedidoAh;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Versao completa, para a tela de detalhe.
 *
 * @property-read PedidoAh $resource
 *
 * @mixin PedidoAh
 */
class PedidoAhResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'identificador' => $this->identificador,
            'numero'        => $this->numero,
            'ano'           => $this->ano,

            'status'       => $this->status?->value,
            'status_label' => $this->status?->label(),
            'status_cor'   => $this->status?->cor(),
            'fase'         => $this->status?->fase()->value,
            'fase_label'   => $this->status?->fase()->label(),

            'municipio' => $this->whenLoaded('municipio', fn (): ?array => $this->municipio ? [
                'id'   => $this->municipio->id,
                'nome' => $this->municipio->nome ?? null,
                'uf'   => $this->municipio->uf ?? null,
            ] : null),

            'pop_atendida'        => $this->pop_atendida,
            'esforcos_realizados' => $this->esforcos_realizados,

            'decreto' => [
                'vigente'  => (bool) $this->decreto_se_ecp_vig,
                'tipo'     => $this->tipo_decreto?->value,
                'tipo_label' => $this->tipo_decreto?->label(),
                'numero'   => $this->numero_decreto,
                'vigencia' => $this->vigencia_decreto?->toDateString(),
            ],

            'coordenador' => [
                'nome'     => $this->nome_coordenador,
                'telefone' => $this->tel_coordenador,
                'celular'  => $this->cel_coordenador,
                'email'    => $this->email_coordenador,
            ],

            'prefeito' => [
                'nome'     => $this->nome_prefeito,
                'telefone' => $this->tel_prefeito,
                'celular'  => $this->cel_prefeito,
                'email'    => $this->email_prefeito,
            ],

            'itens_solicitados' => $this->whenLoaded('itensPedido', fn (): array => $this->itensPedido
                ->map(static fn ($item): array => [
                    'id'                   => $item->id,
                    'descricao_item'       => $item->descricao_item,
                    'qtd'                  => $item->qtd,
                    'qtd_familia_atendida' => $item->qtd_familia_atendida,
                ])->all()),

            'itens_liberados' => $this->whenLoaded('itensLiberados', fn (): array => $this->itensLiberados
                ->map(static fn ($item): array => [
                    'id'                   => $item->id,
                    'descricao_item'       => $item->descricao_item,
                    'qtd'                  => $item->qtd,
                    'qtd_familia_atendida' => $item->qtd_familia_atendida,
                ])->all()),

            'data_entrada_sistema' => $this->data_entrada_sistema?->toDateString(),
            'data_hora_envio'      => $this->data_hora_envio?->toDateString(),
            'data_aprovacao'       => $this->data_aprovacao?->toDateString(),
        ];
    }
}
