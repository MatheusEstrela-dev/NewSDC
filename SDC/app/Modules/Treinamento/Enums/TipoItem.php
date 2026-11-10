<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Enums;

enum TipoItem: string
{
    case PDF = 'PDF';
    case PROJETO = 'PROJETO';

    public function getLabel(): string
    {
        return match ($this) {
            self::PDF => 'Material em PDF',
            self::PROJETO => 'Projeto',
        };
    }
}
