<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Domain\ValueObjects;

/**
 * Value Object: Categoria de Produto
 */
enum CategoriaProduto: string
{
    case ALIMENTO = 'ALIMENTO';
    case HIGIENE = 'HIGIENE';
    case VESTUARIO = 'VESTUARIO';
    case MEDICAMENTO = 'MEDICAMENTO';
    case LIMPEZA = 'LIMPEZA';
    case OUTRO = 'OUTRO';

    /**
     * Retorna label amigável
     */
    public function getLabel(): string
    {
        return match($this) {
            self::ALIMENTO => 'Alimento',
            self::HIGIENE => 'Higiene',
            self::VESTUARIO => 'Vestuário',
            self::MEDICAMENTO => 'Medicamento',
            self::LIMPEZA => 'Limpeza',
            self::OUTRO => 'Outro',
        };
    }

    /**
     * Retorna cor para badge no frontend
     */
    public function getBadgeColor(): string
    {
        return match($this) {
            self::ALIMENTO => 'green',
            self::HIGIENE => 'blue',
            self::VESTUARIO => 'purple',
            self::MEDICAMENTO => 'red',
            self::LIMPEZA => 'yellow',
            self::OUTRO => 'gray',
        };
    }

    /**
     * Retorna ícone para representação visual
     */
    public function getIcon(): string
    {
        return match($this) {
            self::ALIMENTO => 'shopping-cart',
            self::HIGIENE => 'sparkles',
            self::VESTUARIO => 'user',
            self::MEDICAMENTO => 'beaker',
            self::LIMPEZA => 'trash',
            self::OUTRO => 'cube',
        };
    }

    /**
     * Lista todas as categorias disponíveis para select
     */
    public static function toSelectArray(): array
    {
        return array_map(
            fn(self $categoria) => [
                'value' => $categoria->value,
                'label' => $categoria->getLabel(),
                'color' => $categoria->getBadgeColor(),
                'icon' => $categoria->getIcon(),
            ],
            self::cases()
        );
    }
}
