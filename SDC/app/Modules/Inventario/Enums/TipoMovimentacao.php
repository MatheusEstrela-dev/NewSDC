<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Enums;

/**
 * Valores de inventario_ti_movimentacoes.tipo. A coluna continua string sem
 * cast no model: MovimentacaoService compara strings e nao deve quebrar.
 */
enum TipoMovimentacao: string
{
    case EMPRESTIMO = 'emprestimo';
    case REMANEJAMENTO = 'remanejamento';
    // Equipamento que a pessoa remanejada deixou para tras.
    case LIBERACAO = 'liberacao';

    public function label(): string
    {
        return match ($this) {
            self::EMPRESTIMO => 'Empréstimo',
            self::REMANEJAMENTO => 'Remanejamento',
            self::LIBERACAO => 'Liberação',
        };
    }
}
