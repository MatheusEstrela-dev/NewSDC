<?php

namespace App\Modules\PlanCon\Domain\ValueObjects;

enum SituacaoPlano: string
{
    case REGULAR = 'regular';
    case IRREGULAR = 'irregular';

    public function label(): string
    {
        return match ($this) {
            self::REGULAR => 'Regular',
            self::IRREGULAR => 'Irregular',
        };
    }

    public function isRegular(): bool
    {
        return $this === self::REGULAR;
    }
}
