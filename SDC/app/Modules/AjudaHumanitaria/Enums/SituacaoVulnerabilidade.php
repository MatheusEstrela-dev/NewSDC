<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Enums;

enum SituacaoVulnerabilidade: string
{
    case DESABRIGADO = 'desabrigado';
    case DESALOJADO = 'desalojado';
    case DESAPARECIDO = 'desaparecido';
    case FERIDO = 'ferido';
    case ENFERMO = 'enfermo';
    case ISOLADO = 'isolado';
    case AFETADO = 'afetado';

    public static function toSelectArray(): array
    {
        return [
            ['value' => self::DESABRIGADO->value, 'label' => 'Desabrigado'],
            ['value' => self::DESALOJADO->value, 'label' => 'Desalojado'],
            ['value' => self::DESAPARECIDO->value, 'label' => 'Desaparecido'],
            ['value' => self::FERIDO->value, 'label' => 'Ferido'],
            ['value' => self::ENFERMO->value, 'label' => 'Enfermo'],
            ['value' => self::ISOLADO->value, 'label' => 'Isolado'],
            ['value' => self::AFETADO->value, 'label' => 'Afetado'],
        ];
    }
}
