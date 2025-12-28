<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Domain\ValueObjects;

/**
 * Value Object: Tipo de Movimentação de Estoque
 */
enum TipoMovimentacao: string
{
    case ENTRADA = 'ENTRADA';
    case SAIDA = 'SAIDA';
    case AJUSTE = 'AJUSTE';
    case PERDA = 'PERDA';

    /**
     * Retorna label amigável
     */
    public function getLabel(): string
    {
        return match($this) {
            self::ENTRADA => 'Entrada',
            self::SAIDA => 'Saída',
            self::AJUSTE => 'Ajuste',
            self::PERDA => 'Perda',
        };
    }

    /**
     * Retorna cor para badge no frontend
     */
    public function getBadgeColor(): string
    {
        return match($this) {
            self::ENTRADA => 'green',
            self::SAIDA => 'blue',
            self::AJUSTE => 'yellow',
            self::PERDA => 'red',
        };
    }

    /**
     * Verifica se adiciona ao estoque
     */
    public function adicionaEstoque(): bool
    {
        return $this === self::ENTRADA;
    }

    /**
     * Verifica se subtrai do estoque
     */
    public function subtraiEstoque(): bool
    {
        return in_array($this, [
            self::SAIDA,
            self::PERDA,
        ]);
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
