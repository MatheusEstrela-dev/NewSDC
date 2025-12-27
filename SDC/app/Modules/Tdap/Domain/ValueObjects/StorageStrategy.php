<?php

namespace App\Modules\Tdap\Domain\ValueObjects;

/**
 * Estratégia de armazenamento e saída de estoque
 */
enum StorageStrategy: string
{
    case FIFO = 'fifo'; // First In, First Out (padrão para não perecíveis)
    case FEFO = 'fefo'; // First Expire, First Out (perecíveis com validade)
    case LIFO = 'lifo'; // Last In, First Out (raro, mas disponível)

    public function getLabel(): string
    {
        return match($this) {
            self::FIFO => 'FIFO (Primeiro a Entrar, Primeiro a Sair)',
            self::FEFO => 'FEFO (Primeiro a Vencer, Primeiro a Sair)',
            self::LIFO => 'LIFO (Último a Entrar, Primeiro a Sair)',
        };
    }

    public function getDescription(): string
    {
        return match($this) {
            self::FIFO => 'Ideal para produtos não perecíveis. O lote mais antigo sai primeiro.',
            self::FEFO => 'Obrigatório para perecíveis. O lote com data de validade mais próxima sai primeiro.',
            self::LIFO => 'O lote mais recente sai primeiro. Pouco utilizado.',
        };
    }

    /**
     * Retorna a cláusula ORDER BY para a query de seleção de lotes
     */
    public function getOrderByClause(): string
    {
        return match($this) {
            self::FIFO => 'data_entrada ASC',
            self::FEFO => 'data_validade ASC NULLS LAST, data_entrada ASC',
            self::LIFO => 'data_entrada DESC',
        };
    }
}
