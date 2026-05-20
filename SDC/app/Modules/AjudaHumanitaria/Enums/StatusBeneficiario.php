<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Enums;

enum StatusBeneficiario: string
{
    case ATIVO = 'ativo';
    case INATIVO = 'inativo';
    case PENDENTE = 'pendente';
    case SUSPENSO = 'suspenso';
    case FALECIDO = 'falecido';

    public static function toSelectArray(): array
    {
        return [
            ['value' => self::ATIVO->value, 'label' => 'Ativo'],
            ['value' => self::INATIVO->value, 'label' => 'Inativo'],
            ['value' => self::PENDENTE->value, 'label' => 'Pendente'],
            ['value' => self::SUSPENSO->value, 'label' => 'Suspenso'],
            ['value' => self::FALECIDO->value, 'label' => 'Falecido'],
        ];
    }
}
