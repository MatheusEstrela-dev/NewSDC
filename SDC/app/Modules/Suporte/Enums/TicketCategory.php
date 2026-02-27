<?php

declare(strict_types=1);

namespace App\Modules\Suporte\Enums;

enum TicketCategory: string
{
    case ERROR = 'error';
    case DOUBT = 'doubt';
    case SUGGESTION = 'suggestion';
    case OTHER = 'other';

    public function label(): string
    {
        return match($this) {
            self::ERROR => 'Erro no Sistema',
            self::DOUBT => 'Duvida de Uso',
            self::SUGGESTION => 'Sugestao',
            self::OTHER => 'Outro',
        };
    }
}
