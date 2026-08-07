<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Enums;

/**
 * Situacao da prestacao de contas do pedido (RN-19).
 */
enum StatusPrestacaoConta: string
{
    case Pendente     = 'pendente';
    case EmLancamento = 'em_lancamento';
    case Homologada   = 'homologada';

    public function label(): string
    {
        return match ($this) {
            self::Pendente     => 'Pendente',
            self::EmLancamento => 'Em lançamento',
            self::Homologada   => 'Homologada',
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
