<?php

namespace App\Modules\Suporte\Domain\ValueObjects;

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
            self::DOUBT => 'Dúvida de Uso',
            self::SUGGESTION => 'Sugestão',
            self::OTHER => 'Outro',
        };
    }
}
