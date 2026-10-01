<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Enums;

enum StatusMovimentacao: string
{
    case ATIVO = 'ativo';
    case DEVOLVIDO = 'devolvido';
    // Remanejamento que perdeu efeito porque o equipamento foi remanejado de novo.
    case SUBSTITUIDA = 'substituida';

    public function label(): string
    {
        return match ($this) {
            self::ATIVO => 'Ativo',
            self::DEVOLVIDO => 'Devolvido',
            self::SUBSTITUIDA => 'Substituída',
        };
    }

    public static function rotulo(string $valor): string
    {
        return self::tryFrom($valor)?->label() ?? $valor;
    }
}
