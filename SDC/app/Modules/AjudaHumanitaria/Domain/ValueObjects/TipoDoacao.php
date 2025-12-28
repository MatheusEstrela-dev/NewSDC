<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Domain\ValueObjects;

/**
 * Value Object: Tipo de Doação
 */
enum TipoDoacao: string
{
    case MONETARIA = 'MONETARIA';
    case MATERIAL = 'MATERIAL';

    /**
     * Retorna label amigável
     */
    public function getLabel(): string
    {
        return match($this) {
            self::MONETARIA => 'Monetária',
            self::MATERIAL => 'Material',
        };
    }

    /**
     * Retorna cor para badge no frontend
     */
    public function getBadgeColor(): string
    {
        return match($this) {
            self::MONETARIA => 'green',
            self::MATERIAL => 'blue',
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
