<?php

declare(strict_types=1);

namespace App\Support\Database;

use Illuminate\Support\Facades\DB;

/**
 * Operador de busca textual que ignora maiuscula/minuscula em qualquer driver.
 *
 * No Postgres LIKE diferencia caixa e ILIKE nao; no MySQL LIKE ja ignora pela
 * collation. Centralizado para as buscas do admin nao divergirem entre si.
 */
final class OperadorLike
{
    public static function insensivel(): string
    {
        return DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';
    }
}
