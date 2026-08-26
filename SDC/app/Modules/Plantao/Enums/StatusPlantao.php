<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Enums;

enum StatusPlantao: string
{
    case ATIVO = 'ATIVO';
    case PENDENTE_ACEITE = 'PENDENTE_ACEITE';
    case FINALIZADO = 'FINALIZADO';
    case FINALIZADO_COM_DIVERGENCIA = 'FINALIZADO_COM_DIVERGENCIA';

    public function label(): string
    {
        return match ($this) {
            self::ATIVO => 'Ativo',
            self::PENDENTE_ACEITE => 'Pendente de aceite',
            self::FINALIZADO => 'Finalizado',
            self::FINALIZADO_COM_DIVERGENCIA => 'Finalizado com divergencia',
        };
    }

    /**
     * O turno ja saiu do ar: nao aceita mais movimentacao nem novo snapshot.
     */
    public function encerrado(): bool
    {
        return $this === self::FINALIZADO
            || $this === self::FINALIZADO_COM_DIVERGENCIA;
    }

    public static function toSelectArray(): array
    {
        return array_map(
            fn(self $status) => [
                'value' => $status->value,
                'label' => $status->label(),
            ],
            self::cases()
        );
    }
}
