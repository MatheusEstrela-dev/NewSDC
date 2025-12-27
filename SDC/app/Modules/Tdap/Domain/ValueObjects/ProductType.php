<?php

namespace App\Modules\Tdap\Domain\ValueObjects;

enum ProductType: string
{
    case CESTA_BASICA = 'cesta_basica';
    case KIT_LIMPEZA = 'kit_limpeza';
    case COLCHAO = 'colchao';
    case OUTROS = 'outros';

    public function getLabel(): string
    {
        return match($this) {
            self::CESTA_BASICA => 'Cesta Básica',
            self::KIT_LIMPEZA => 'Kit Limpeza',
            self::COLCHAO => 'Colchão',
            self::OUTROS => 'Outros',
        };
    }

    public function getColorClass(): string
    {
        return match($this) {
            self::CESTA_BASICA => 'bg-green-100 text-green-800',
            self::KIT_LIMPEZA => 'bg-blue-100 text-blue-800',
            self::COLCHAO => 'bg-purple-100 text-purple-800',
            self::OUTROS => 'bg-gray-100 text-gray-800',
        };
    }

    public function isPerecivel(): bool
    {
        return match($this) {
            self::CESTA_BASICA => true,
            self::KIT_LIMPEZA => true,
            self::COLCHAO => false,
            self::OUTROS => false,
        };
    }

    public function getGrupoRisco(): string
    {
        return match($this) {
            self::CESTA_BASICA => 'ALIMENTO',
            self::KIT_LIMPEZA => 'QUIMICO',
            self::COLCHAO => 'GERAL',
            self::OUTROS => 'GERAL',
        };
    }

    public function getIcon(): string
    {
        return match($this) {
            self::CESTA_BASICA => 'shopping-basket',
            self::KIT_LIMPEZA => 'spray-can',
            self::COLCHAO => 'bed',
            self::OUTROS => 'box',
        };
    }
}
