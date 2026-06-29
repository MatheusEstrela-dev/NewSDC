<?php

declare(strict_types=1);

namespace App\Modules\Pmda\Enums;

enum SolicitacaoComunidadeStatus: string
{
    case PENDENTE  = 'PENDENTE';
    case APROVADA  = 'APROVADA';
    case REJEITADA = 'REJEITADA';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDENTE  => 'Disponível p/ análise CEDEC',
            self::APROVADA  => 'Aprovada',
            self::REJEITADA => 'Rejeitada',
        };
    }

    public function label(): string
    {
        return $this->getLabel();
    }

    public function getColorClass(): string
    {
        return match ($this) {
            self::PENDENTE  => 'bg-amber-100 text-amber-800',
            self::APROVADA  => 'bg-green-100 text-green-800',
            self::REJEITADA => 'bg-red-100 text-red-800',
        };
    }
}
