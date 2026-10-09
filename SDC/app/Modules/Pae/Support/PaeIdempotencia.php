<?php

declare(strict_types=1);

namespace App\Modules\Pae\Support;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

/**
 * Mesma chave de idempotencia com dados de negocio diferentes e reuso indevido,
 * nao repeticao. O jsonb nao preserva a ordem das chaves e devolve 2 onde o
 * cliente mandou 2.0; a comparacao tolera as duas coisas e nada alem disso.
 */
final class PaeIdempotencia
{
    private const EPSILON = 1e-9;

    /**
     * @param  list<string>  $campos
     */
    public static function exigirMesmosDados(Model $existente, array $dados, array $campos): void
    {
        foreach ($campos as $campo) {
            if (! self::iguais($existente->getAttribute($campo), $dados[$campo] ?? null)) {
                throw ValidationException::withMessages([
                    'chave_idempotencia' => 'Esta chave de idempotência já foi usada com outros dados.',
                ]);
            }
        }
    }

    private static function iguais(mixed $a, mixed $b): bool
    {
        if (is_array($a) || is_array($b)) {
            return is_array($a) && is_array($b) && self::arraysIguais($a, $b);
        }
        if ((is_int($a) || is_float($a)) && (is_int($b) || is_float($b))) {
            return abs((float) $a - (float) $b) < self::EPSILON;
        }

        return self::normalizar($a) === self::normalizar($b);
    }

    private static function arraysIguais(array $a, array $b): bool
    {
        if (count($a) !== count($b)) {
            return false;
        }
        foreach ($a as $chave => $valor) {
            if (! array_key_exists($chave, $b) || ! self::iguais($valor, $b[$chave])) {
                return false;
            }
        }

        return true;
    }

    private static function normalizar(mixed $valor): string
    {
        return match (true) {
            $valor instanceof DateTimeInterface => $valor->format('Y-m-d'),
            is_bool($valor) => $valor ? '1' : '0',
            default => (string) $valor,
        };
    }
}
