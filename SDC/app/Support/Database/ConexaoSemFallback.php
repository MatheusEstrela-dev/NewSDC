<?php

declare(strict_types=1);

namespace App\Support\Database;

use RuntimeException;

/**
 * Conexao dedicada SEM fallback para a default: sem a chave em
 * config('database.<CHAVE>') o recurso esta desligado e exigirAtiva() lanca,
 * para que nada grave ou leia no sdc por engano.
 *
 * Quem usa define as constantes CHAVE (chave em config/database.php) e
 * VARIAVEL (env que liga o recurso, citada no erro).
 */
trait ConexaoSemFallback
{
    /**
     * Ligada so com a chave definida e apontando para fora da conexao default.
     */
    public static function ativa(): bool
    {
        $nome = (string) config('database.'.self::CHAVE);

        return $nome !== '' && $nome !== (string) config('database.default');
    }

    public static function exigirAtiva(): string
    {
        if (! self::ativa()) {
            throw new RuntimeException(static::class.' desligada: defina '.self::VARIAVEL.'.');
        }

        return (string) config('database.'.self::CHAVE);
    }
}
