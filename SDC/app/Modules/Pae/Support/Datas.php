<?php

declare(strict_types=1);

namespace App\Modules\Pae\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Normalizacao de datas para as regras de prazo do PAE: tudo comparado por dia,
 * a meia-noite, em CarbonImmutable (nenhuma regra muta a data recebida).
 */
final class Datas
{
    public static function dia(mixed $valor): ?CarbonImmutable
    {
        if ($valor instanceof \DateTimeInterface) {
            return CarbonImmutable::instance($valor)->startOfDay();
        }

        if ($valor === null || $valor === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse((string) $valor)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    public static function hoje(?CarbonInterface $hoje = null): CarbonImmutable
    {
        return CarbonImmutable::instance($hoje ?? CarbonImmutable::now())->startOfDay();
    }
}
