<?php

namespace App\Support\Database;

use Illuminate\Support\Facades\DB;

/**
 * Helper para emitir SQL compativel com mysql e postgres.
 * Os argumentos sao tratados como identificadores/literais ja sanitizados
 * pelo chamador — NUNCA passe input do usuario.
 */
final class PgCompat
{
    public static function isPgsql(?string $connection = null): bool
    {
        return DB::connection($connection)->getDriverName() === 'pgsql';
    }

    /**
     * SPLIT_PART (PG) vs SUBSTRING_INDEX (mysql).
     * Em mysql usa indice negativo do SUBSTRING_INDEX para emular a posicao do PG.
     */
    public static function splitPart(string $column, string $delimiter, int $index): string
    {
        $delim = str_replace("'", "''", $delimiter);

        if (self::isPgsql()) {
            return sprintf("SPLIT_PART(%s, '%s', %d)", $column, $delim, $index);
        }

        return sprintf("SUBSTRING_INDEX(SUBSTRING_INDEX(%s, '%s', %d), '%s', -1)", $column, $delim, $index, $delim);
    }

    public static function castToInt(string $expression): string
    {
        return self::isPgsql()
            ? sprintf('(%s)::int', $expression)
            : sprintf('CAST(%s AS UNSIGNED)', $expression);
    }

    public static function extractDatePart(string $part, string $column): string
    {
        $part = strtoupper($part);

        if (self::isPgsql()) {
            return sprintf('EXTRACT(%s FROM %s)::int', $part, $column);
        }

        return sprintf('%s(%s)', $part, $column);
    }

    public static function dateDiffDays(string $endDate, string $startDate): string
    {
        if (self::isPgsql()) {
            return sprintf('(%s::date - %s::date)', $endDate, $startDate);
        }

        return sprintf('DATEDIFF(%s, %s)', $endDate, $startDate);
    }

    public static function dateAddDays(string $column, string $daysExpr): string
    {
        if (self::isPgsql()) {
            return sprintf('(%s + MAKE_INTERVAL(days => (%s)::int))', $column, $daysExpr);
        }

        return sprintf('DATE_ADD(%s, INTERVAL %s DAY)', $column, $daysExpr);
    }

    public static function currentDate(): string
    {
        return self::isPgsql() ? 'CURRENT_DATE' : 'CURDATE()';
    }
}
