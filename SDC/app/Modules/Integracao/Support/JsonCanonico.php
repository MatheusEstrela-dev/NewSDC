<?php

declare(strict_types=1);

namespace App\Modules\Integracao\Support;

/**
 * JSON com chaves de objeto ordenadas: o mesmo conteudo em ordem diferente
 * gera o mesmo hash (base da comparacao da Idempotency-Key).
 */
final class JsonCanonico
{
    private const FLAGS = JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION;

    public static function codificar(mixed $valor): string
    {
        return json_encode(self::ordenar($valor), self::FLAGS);
    }

    /** sha256 em hex do JSON canonico. */
    public static function sha256(mixed $valor): string
    {
        return hash('sha256', self::codificar($valor));
    }

    private static function ordenar(mixed $valor): mixed
    {
        if (! is_array($valor)) {
            return $valor;
        }

        if (! array_is_list($valor)) {
            ksort($valor, SORT_STRING);
        }

        return array_map(self::ordenar(...), $valor);
    }
}
