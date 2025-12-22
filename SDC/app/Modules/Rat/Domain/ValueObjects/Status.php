<?php

namespace App\Modules\Rat\Domain\ValueObjects;

enum Status: string
{
    case RASCUNHO = 'rascunho';
    case EM_ANDAMENTO = 'em_andamento';
    case FINALIZADO = 'finalizado';
    case ARQUIVADO = 'arquivado';

    public function getLabel(): string
    {
        return match($this) {
            self::RASCUNHO => 'Rascunho',
            self::EM_ANDAMENTO => 'Em Andamento',
            self::FINALIZADO => 'Finalizado',
            self::ARQUIVADO => 'Arquivado',
        };
    }

    public function getColorClass(): string
    {
        return match($this) {
            self::RASCUNHO => 'warning',
            self::EM_ANDAMENTO => 'info',
            self::FINALIZADO => 'success',
            self::ARQUIVADO => 'secondary',
        };
    }
}

