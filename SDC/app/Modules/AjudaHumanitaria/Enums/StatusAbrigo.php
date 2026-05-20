<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Enums;

enum StatusAbrigo: string
{
    case ATIVO = 'ativo';
    case LOTADO = 'lotado';
    case DESATIVADO = 'desativado';
    case EM_MANUTENCAO = 'em_manutencao';

    public static function toSelectArray(): array
    {
        return [
            ['value' => self::ATIVO->value, 'label' => 'Ativo'],
            ['value' => self::LOTADO->value, 'label' => 'Lotado'],
            ['value' => self::DESATIVADO->value, 'label' => 'Desativado'],
            ['value' => self::EM_MANUTENCAO->value, 'label' => 'Em Manutencao'],
        ];
    }
}
