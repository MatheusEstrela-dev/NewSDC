<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Enums;

/**
 * Tipo do decreto que embasa o pedido (RN-06).
 */
enum TipoDecreto: string
{
    case SituacaoEmergencia        = 'SE';
    case EstadoCalamidadePublica   = 'ECP';

    public function label(): string
    {
        return match ($this) {
            self::SituacaoEmergencia      => 'Situação de Emergência',
            self::EstadoCalamidadePublica => 'Estado de Calamidade Pública',
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
