<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Domain\ValueObjects;

/**
 * Value Object: Tipo de Local do Abrigo
 */
enum TipoLocalAbrigo: string
{
    case GINASIO = 'GINASIO';
    case ESCOLA = 'ESCOLA';
    case IGREJA = 'IGREJA';
    case OUTRO = 'OUTRO';

    /**
     * Retorna label amigável
     */
    public function getLabel(): string
    {
        return match($this) {
            self::GINASIO => 'Ginásio',
            self::ESCOLA => 'Escola',
            self::IGREJA => 'Igreja',
            self::OUTRO => 'Outro',
        };
    }

    /**
     * Retorna cor para badge no frontend
     */
    public function getBadgeColor(): string
    {
        return match($this) {
            self::GINASIO => 'blue',
            self::ESCOLA => 'green',
            self::IGREJA => 'purple',
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
