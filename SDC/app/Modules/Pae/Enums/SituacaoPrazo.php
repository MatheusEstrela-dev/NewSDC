<?php

declare(strict_types=1);

namespace App\Modules\Pae\Enums;

/**
 * Situacao do prazo de analise do protocolo (Art. 9), lida pela listagem.
 */
enum SituacaoPrazo: string
{
    case OK = 'ok';
    case PROXIMO = 'proximo';
    case VENCIDO = 'vencido';
    case PAUSADO = 'pausado';
    case SEM_DATA = 'sem_data';
}
