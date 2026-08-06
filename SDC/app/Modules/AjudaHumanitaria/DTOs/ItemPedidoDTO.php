<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\DTOs;

use App\Modules\AjudaHumanitaria\Enums\TipoItemPedido;

/**
 * Item do pedido (RN-08). O tipo distingue o solicitado pelo municipio do
 * liberado pelo CEDEC, e por padrao e o solicitado.
 */
final readonly class ItemPedidoDTO
{
    public function __construct(
        public string $descricaoItem,
        public int $qtd,
        public int $qtdFamiliaAtendida,
        public TipoItemPedido $tipo = TipoItemPedido::Pedido,
        public ?int $materialAhId = null,
        public ?string $codigo = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromRequest(array $data): self
    {
        $tipo = isset($data['tipo']) && $data['tipo'] !== ''
            ? TipoItemPedido::from((string) $data['tipo'])
            : TipoItemPedido::Pedido;

        return new self(
            descricaoItem:      (string) ($data['descricao_item'] ?? ''),
            qtd:                (int) ($data['qtd'] ?? 0),
            qtdFamiliaAtendida: (int) ($data['qtd_familia_atendida'] ?? 0),
            tipo:               $tipo,
            materialAhId:       isset($data['material_ah_id']) ? (int) $data['material_ah_id'] : null,
            codigo:             isset($data['codigo']) ? (string) $data['codigo'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'material_ah_id'       => $this->materialAhId,
            'codigo'               => $this->codigo,
            'descricao_item'       => $this->descricaoItem,
            'qtd'                  => $this->qtd,
            'qtd_familia_atendida' => $this->qtdFamiliaAtendida,
            'tipo'                 => $this->tipo->value,
        ];
    }
}
