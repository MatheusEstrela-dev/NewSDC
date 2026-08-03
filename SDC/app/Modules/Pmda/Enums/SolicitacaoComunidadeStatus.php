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

    /**
     * Cor do status na paleta do Badge. Nome de cor, nao classe Tailwind: a receita
     * de pill vive no componente, nao no dominio. Ver PmdaStatus::getCor().
     */
    public function getCor(): string
    {
        return match ($this) {
            self::PENDENTE  => 'amber',
            self::APROVADA  => 'green',
            self::REJEITADA => 'red',
        };
    }

    /**
     * @deprecated Use getCor().
     */
    public function getColorClass(): string
    {
        return match ($this) {
            self::PENDENTE  => 'bg-amber-100 text-amber-800',
            self::APROVADA  => 'bg-green-100 text-green-800',
            self::REJEITADA => 'bg-red-100 text-red-800',
        };
    }
}
