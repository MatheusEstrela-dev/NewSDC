<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Domain\ValueObjects;

/**
 * Value Object: Tipo de Movimentação Financeira
 */
enum TipoMovimentacaoFinanceira: string
{
    case ENTRADA = 'ENTRADA';
    case SAIDA = 'SAIDA';

    /**
     * Retorna label amigável
     */
    public function getLabel(): string
    {
        return match($this) {
            self::ENTRADA => 'Entrada',
            self::SAIDA => 'Saída',
        };
    }

    /**
     * Retorna cor para badge no frontend
     */
    public function getBadgeColor(): string
    {
        return match($this) {
            self::ENTRADA => 'green',
            self::SAIDA => 'red',
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
