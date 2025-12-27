<?php

namespace App\Modules\Tdap\Domain\ValueObjects;

enum MovimentacaoType: string
{
    case ENTRADA = 'entrada';
    case SAIDA = 'saida';
    case TRANSFERENCIA = 'transferencia';
    case AJUSTE = 'ajuste';
    case DEVOLUCAO = 'devolucao';

    public function getLabel(): string
    {
        return match($this) {
            self::ENTRADA => 'Entrada',
            self::SAIDA => 'Saída',
            self::TRANSFERENCIA => 'Transferência',
            self::AJUSTE => 'Ajuste',
            self::DEVOLUCAO => 'Devolução',
        };
    }

    public function getColorClass(): string
    {
        return match($this) {
            self::ENTRADA => 'bg-green-100 text-green-800',
            self::SAIDA => 'bg-red-100 text-red-800',
            self::TRANSFERENCIA => 'bg-blue-100 text-blue-800',
            self::AJUSTE => 'bg-yellow-100 text-yellow-800',
            self::DEVOLUCAO => 'bg-purple-100 text-purple-800',
        };
    }

    public function getIcon(): string
    {
        return match($this) {
            self::ENTRADA => 'arrow-down-circle',
            self::SAIDA => 'arrow-up-circle',
            self::TRANSFERENCIA => 'arrow-right-left',
            self::AJUSTE => 'wrench',
            self::DEVOLUCAO => 'arrow-uturn-left',
        };
    }

    public function getMultiplicador(): int
    {
        return match($this) {
            self::ENTRADA, self::DEVOLUCAO => 1,
            self::SAIDA => -1,
            self::TRANSFERENCIA, self::AJUSTE => 0, // Handled separately
        };
    }
}
