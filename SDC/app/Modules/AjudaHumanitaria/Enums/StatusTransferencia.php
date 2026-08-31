<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Enums;

/**
 * Situacao da transferencia entre depositos, herdada de
 * aju_transferencia.situacao.
 *
 * O dado migrado separa dois casos com clareza: as 66 em 1 tem data de saida e
 * de chegada e nenhum motivo; as 3 em 2 tem motivo preenchido, todas com
 * "CORRECAO DE LANCAMENTOS". Dai Concluida e Cancelada.
 *
 * EmTransito nao aparece na carga do legado, mas e o default da coluna e o
 * estado natural de uma transferencia criada pelo sistema novo: saiu do
 * deposito de origem e ainda nao chegou ao destino.
 */
enum StatusTransferencia: int
{
    case EmTransito = 0;
    case Concluida  = 1;
    case Cancelada  = 2;

    public function label(): string
    {
        return match ($this) {
            self::EmTransito => 'Em trânsito',
            self::Concluida  => 'Concluída',
            self::Cancelada  => 'Cancelada',
        };
    }

    /** Variante do design system usada por badge e cartao. */
    public function cor(): string
    {
        return match ($this) {
            self::EmTransito => 'warning',
            self::Concluida  => 'success',
            self::Cancelada  => 'danger',
        };
    }

    /**
     * @return array<int, array{value: int, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $caso) => ['value' => $caso->value, 'label' => $caso->label()],
            self::cases(),
        );
    }
}
