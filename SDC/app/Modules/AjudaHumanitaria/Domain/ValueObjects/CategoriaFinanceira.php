<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Domain\ValueObjects;

/**
 * Value Object: Categoria Financeira
 */
enum CategoriaFinanceira: string
{
    case DOACAO = 'DOACAO';
    case COMPRA_MATERIAL = 'COMPRA_MATERIAL';
    case AUXILIO_FINANCEIRO = 'AUXILIO_FINANCEIRO';
    case DESPESA_OPERACIONAL = 'DESPESA_OPERACIONAL';
    case OUTRA = 'OUTRA';

    /**
     * Retorna label amigável
     */
    public function getLabel(): string
    {
        return match($this) {
            self::DOACAO => 'Doação',
            self::COMPRA_MATERIAL => 'Compra de Material',
            self::AUXILIO_FINANCEIRO => 'Auxílio Financeiro',
            self::DESPESA_OPERACIONAL => 'Despesa Operacional',
            self::OUTRA => 'Outra',
        };
    }

    /**
     * Retorna cor para badge no frontend
     */
    public function getBadgeColor(): string
    {
        return match($this) {
            self::DOACAO => 'green',
            self::COMPRA_MATERIAL => 'blue',
            self::AUXILIO_FINANCEIRO => 'purple',
            self::DESPESA_OPERACIONAL => 'yellow',
            self::OUTRA => 'gray',
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
            ],
            self::cases()
        );
    }
}
