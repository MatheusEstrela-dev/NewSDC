<?php

declare(strict_types=1);

namespace App\Modules\Pae\Support;

use Carbon\CarbonImmutable;

final class PaeDcoCiclo
{
    public static function competenciaExigivel(CarbonImmutable $data): int
    {
        return $data->month <= 6 ? $data->year - 1 : $data->year;
    }

    public static function vencimento(int $competencia): CarbonImmutable
    {
        return CarbonImmutable::create($competencia, 6, 30, 0, 0, 0);
    }
}
