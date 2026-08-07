<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Enums;

/**
 * Situacao da liberacao de material, herdada de aju_liberacao.situacao.
 *
 * O legado nao documenta os codigos, mas o dado migrado nao deixa duvida:
 * das 3.273 liberacoes em 1, todas tem recibo pago; das 290 em 2, todas tem
 * data de cancelamento; as 19 em 0 nao tem nem recibo nem cancelamento.
 *
 * Inteiro, e nao string, porque a coluna e smallint e veio assim do legado.
 */
enum StatusLiberacao: int
{
    case Pendente  = 0;
    case Paga      = 1;
    case Cancelada = 2;

    public function label(): string
    {
        return match ($this) {
            self::Pendente  => 'Pendente',
            self::Paga      => 'Paga',
            self::Cancelada => 'Cancelada',
        };
    }

    /** Variante do design system usada por badge e cartao. */
    public function cor(): string
    {
        return match ($this) {
            self::Pendente  => 'warning',
            self::Paga      => 'success',
            self::Cancelada => 'danger',
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
