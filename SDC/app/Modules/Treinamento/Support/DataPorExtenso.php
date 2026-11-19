<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Support;

use Carbon\CarbonInterface;

/**
 * Datas por extenso do certificado ("04 de novembro de 2026"). Os meses ficam
 * aqui, sem depender do locale instalado no PHP da imagem.
 */
final class DataPorExtenso
{
    private const MESES = [
        1 => 'janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho',
        'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro',
    ];

    public static function data(CarbonInterface $data): string
    {
        return sprintf('%02d de %s de %d', $data->day, self::MESES[$data->month], $data->year);
    }

    /**
     * Trecho "realizado ..." do certificado: "no período de 03 a 04 de novembro
     * de 2026", "em 03 de novembro de 2026" (um dia so) ou null sem data.
     */
    public static function periodo(?CarbonInterface $inicio, ?CarbonInterface $fim): ?string
    {
        $inicio ??= $fim;
        $fim ??= $inicio;

        if ($inicio === null || $fim === null) {
            return null;
        }

        if ($inicio->isSameDay($fim)) {
            return 'em '.self::data($inicio);
        }

        $de = match (true) {
            $inicio->year !== $fim->year => self::data($inicio),
            $inicio->month !== $fim->month => sprintf('%02d de %s', $inicio->day, self::MESES[$inicio->month]),
            default => sprintf('%02d', $inicio->day),
        };

        return 'no período de '.$de.' a '.self::data($fim);
    }
}
