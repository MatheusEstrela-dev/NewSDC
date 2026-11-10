<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Enums;

enum StatusEntrega: string
{
    case ENVIADA = 'ENVIADA';
    case APROVADA = 'APROVADA';
    case DEVOLVIDA = 'DEVOLVIDA';

    public function getLabel(): string
    {
        return match ($this) {
            self::ENVIADA => 'Enviada',
            self::APROVADA => 'Aprovada',
            self::DEVOLVIDA => 'Devolvida',
        };
    }
}
