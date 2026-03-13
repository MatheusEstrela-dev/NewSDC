<?php

declare(strict_types=1);

namespace App\Modules\Rat\Enums;

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
    public static function toSelectArray(): array
    {
        $array = [];
        foreach (self::cases() as $case) {
            $array[$case->value] = $case->getLabel();
        }
        return $array;
    }
}
