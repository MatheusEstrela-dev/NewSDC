<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Enums;

/**
 * Discriminador de item do pedido (RN-08).
 *
 * Pedido   = quantidade solicitada pelo municipio.
 * Liberado = quantidade efetivamente liberada pelo CEDEC.
 *
 * Os valores 'P' e 'L' sao preservados do legado (aju_h_pedido_itens.tp_item)
 * para manter a leitura de dados historicos possivel.
 */
enum TipoItemPedido: string
{
    case Pedido   = 'P';
    case Liberado = 'L';

    public function label(): string
    {
        return match ($this) {
            self::Pedido   => 'Solicitado pelo municipio',
            self::Liberado => 'Liberado pelo CEDEC',
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $caso) => ['value' => $caso->value, 'label' => $caso->label()],
            self::cases(),
        );
    }
}
