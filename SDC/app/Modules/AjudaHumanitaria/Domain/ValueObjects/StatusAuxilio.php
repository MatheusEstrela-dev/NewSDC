<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Domain\ValueObjects;

/**
 * Value Object: Status do Auxílio
 */
enum StatusAuxilio: string
{
    case PLANEJADO = 'PLANEJADO';
    case ENTREGUE = 'ENTREGUE';
    case CANCELADO = 'CANCELADO';

    /**
     * Retorna label amigável
     */
    public function getLabel(): string
    {
        return match($this) {
            self::PLANEJADO => 'Planejado',
            self::ENTREGUE => 'Entregue',
            self::CANCELADO => 'Cancelado',
        };
    }

    /**
     * Retorna cor para badge no frontend
     */
    public function getBadgeColor(): string
    {
        return match($this) {
            self::PLANEJADO => 'yellow',
            self::ENTREGUE => 'green',
            self::CANCELADO => 'red',
        };
    }

    /**
     * Verifica se está finalizado
     */
    public function isFinalizado(): bool
    {
        return in_array($this, [
            self::ENTREGUE,
            self::CANCELADO,
        ]);
    }

    /**
     * Lista todos os status disponíveis para select
     */
    public static function toSelectArray(): array
    {
        return array_map(
            fn(self $status) => [
                'value' => $status->value,
                'label' => $status->getLabel(),
                'color' => $status->getBadgeColor(),
            ],
            self::cases()
        );
    }
}
