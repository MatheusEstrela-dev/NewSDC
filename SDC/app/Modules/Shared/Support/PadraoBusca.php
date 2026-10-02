<?php

declare(strict_types=1);

namespace App\Modules\Shared\Support;

/**
 * Padrao de LIKE/ILIKE "contem" a partir do que o usuario digitou. Os curingas
 * % e _ (e a barra de escape) viram texto literal: buscar "100%" nao pode
 * casar com "1000".
 */
final class PadraoBusca
{
    /** Nulo quando o termo, sem espacos nas pontas, fica vazio. */
    public static function contem(?string $termo): ?string
    {
        $termo = trim((string) $termo);

        return $termo === '' ? null : '%'.addcslashes($termo, '%_\\').'%';
    }
}
