<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Domain\ValueObjects;

/**
 * Value Object: Tipo de Auxílio
 */
enum TipoAuxilio: string
{
    case KIT_ALIMENTACAO = 'KIT_ALIMENTACAO';
    case KIT_HIGIENE = 'KIT_HIGIENE';
    case KIT_LIMPEZA = 'KIT_LIMPEZA';
    case MONETARIO = 'MONETARIO';
    case OUTRO = 'OUTRO';

    /**
     * Retorna label amigável
     */
    public function getLabel(): string
    {
        return match($this) {
            self::KIT_ALIMENTACAO => 'Kit Alimentação',
            self::KIT_HIGIENE => 'Kit Higiene',
            self::KIT_LIMPEZA => 'Kit Limpeza',
            self::MONETARIO => 'Monetário',
            self::OUTRO => 'Outro',
        };
    }

    /**
     * Retorna cor para badge no frontend
     */
    public function getBadgeColor(): string
    {
        return match($this) {
            self::KIT_ALIMENTACAO => 'green',
            self::KIT_HIGIENE => 'blue',
            self::KIT_LIMPEZA => 'purple',
            self::MONETARIO => 'yellow',
            self::OUTRO => 'gray',
        };
    }

    /**
     * Lista todos os tipos disponíveis para select
     */
    public static function toSelectArray(): array
    {
        return array_map(
            fn(self $tipo) => [
                'value' => $tipo->value,
                'label' => $tipo->getLabel(),
                'color' => $tipo->getBadgeColor(),
            ],
            self::cases()
        );
    }
}
