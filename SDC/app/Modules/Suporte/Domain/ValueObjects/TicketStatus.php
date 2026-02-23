<?php

namespace App\Modules\Suporte\Domain\ValueObjects;

enum TicketStatus: string
{
    case OPEN = 'open';
    case IN_PROGRESS = 'in_progress';
    case RESOLVED = 'resolved';
    case CLOSED = 'closed';

    public function label(): string
    {
        return match($this) {
            self::OPEN => 'Em Aberto',
            self::IN_PROGRESS => 'Em Andamento',
            self::RESOLVED => 'Resolvido',
            self::CLOSED => 'Fechado',
        };
    }
}
