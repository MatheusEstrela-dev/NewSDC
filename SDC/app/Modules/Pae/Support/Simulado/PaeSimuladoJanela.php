<?php

declare(strict_types=1);

namespace App\Modules\Pae\Support\Simulado;

use Carbon\CarbonImmutable;
use DateTimeInterface;

/**
 * Vigencia do relatorio de simulado: realizacao nos 12 meses anteriores a data
 * de referencia (emissao ou hoje), inclusive nos dois extremos. Datas puras:
 * a hora de qualquer lado e ignorada. 29/02 recua para 28/02 (sem estouro de mes).
 */
final class PaeSimuladoJanela
{
    public const MESES = 12;

    public static function inicio(CarbonImmutable $referencia): CarbonImmutable
    {
        return $referencia->startOfDay()->subMonthsNoOverflow(self::MESES);
    }

    public static function contem(DateTimeInterface|string $realizacao, CarbonImmutable $referencia): bool
    {
        $dia = CarbonImmutable::parse($realizacao)->toDateString();

        return $dia >= self::inicio($referencia)->toDateString() && $dia <= $referencia->toDateString();
    }

    /** Ultimo dia de referencia em que a janela ainda contem a realizacao (realizacao mais 12 meses). */
    public static function vencimento(DateTimeInterface|string $realizacao): CarbonImmutable
    {
        $dia = CarbonImmutable::parse($realizacao)->startOfDay();
        $limite = $dia->addMonthsNoOverflow(self::MESES);
        while (self::inicio($limite->addDay())->toDateString() <= $dia->toDateString()) {
            $limite = $limite->addDay();
        }

        return $limite;
    }
}
