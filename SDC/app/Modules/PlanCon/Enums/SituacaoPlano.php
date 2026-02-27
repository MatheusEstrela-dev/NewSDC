<?php

declare(strict_types=1);

namespace App\Modules\PlanCon\Enums;

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
