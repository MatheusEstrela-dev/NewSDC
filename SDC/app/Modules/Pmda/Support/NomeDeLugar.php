<?php

declare(strict_types=1);

namespace App\Modules\Pmda\Support;

use Illuminate\Support\Str;

/**
 * Comparacao de nomes de lugar (#56, "Olhos d'Agua e um dos toponimos mais
 * comuns do Brasil"). O mesmo lugar chega escrito como "Olhos d'Água",
 * "Olhos D'agua", "Olhos dAgua" ou "OLHOS DAGUA": o LOWER(nome) = ? deixava
 * cada grafia virar uma comunidade nova no mesmo municipio.
 *
 * Normaliza sem acento, sem pontuacao e sem espacos, em minusculas.
 */
final class NomeDeLugar
{
    public static function normalizar(?string $nome): string
    {
        return (string) preg_replace('/[^a-z0-9]/', '', Str::lower(Str::ascii((string) $nome)));
    }

    public static function iguais(?string $a, ?string $b): bool
    {
        $na = self::normalizar($a);

        return $na !== '' && $na === self::normalizar($b);
    }
}
