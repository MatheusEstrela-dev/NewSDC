<?php

declare(strict_types=1);

namespace App\Modules\Compdec\Support;

use Carbon\Carbon;

/**
 * Helpers puros para normalizar valores vindos do banco legado (sdc).
 *
 * Cobre os formatos sujos do legado:
 *  - "Sim/Nao", "S/N", "1/0" como booleano
 *  - Datas em PT-BR (dd/mm/yyyy) ou string vazia
 *  - Decimais com virgula ("2,5") ou ponto ("2.5")
 *  - Strings vazias mapeadas para null
 */
final class LegacyParser
{
    private const TRUE_VALUES = ['sim', 's', '1', 'true', 'on', 'yes', 'y'];

    private function __construct() {}

    public static function toBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if ($value === null || $value === '') {
            return false;
        }

        if (is_numeric($value)) {
            return (int) $value === 1;
        }

        return in_array(mb_strtolower(trim((string) $value)), self::TRUE_VALUES, true);
    }

    public static function toDate(mixed $value): ?Carbon
    {
        if ($value === null || $value === '' || $value === '0000-00-00') {
            return null;
        }

        if ($value instanceof Carbon) {
            return $value;
        }

        $value = trim((string) $value);

        // dd/mm/yyyy
        if (preg_match('#^\d{1,2}/\d{1,2}/\d{4}$#', $value)) {
            try {
                $date = Carbon::createFromFormat('!d/m/Y', $value);
                $errors = Carbon::getLastErrors();

                if (
                    $date === false
                    || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))
                    || $date->format('j/n/Y') !== ltrim(preg_replace('#^0?(\d{1,2})/0?(\d{1,2})/(\d{4})$#', '$1/$2/$3', $value), '0')
                ) {
                    return null;
                }

                return $date->startOfDay();
            } catch (\Throwable) {
                return null;
            }
        }

        // yyyy-mm-dd / ISO
        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    public static function toDecimalBR(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $clean = (string) $value;
        // remove milhar BR (1.234,56 -> 1234,56) e troca virgula por ponto
        $clean = str_replace('.', '', $clean);
        $clean = str_replace(',', '.', $clean);

        return (float) $clean;
    }

    public static function toIntOrNull(mixed $value): ?int
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return null;
        }

        return (int) $value;
    }

    public static function toStringOrNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }
}
