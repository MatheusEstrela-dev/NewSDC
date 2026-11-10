<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Enums;

enum StatusProgresso: string
{
    case EM_ANDAMENTO = 'EM_ANDAMENTO';
    case CONCLUIDO = 'CONCLUIDO';
}
