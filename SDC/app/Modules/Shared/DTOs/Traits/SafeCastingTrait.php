<?php

declare(strict_types=1);

namespace App\Modules\Shared\DTOs\Traits;

use Illuminate\Support\Str;
use Stringable;

/**
 * Conversao de tipos para as factories dos DTOs (fromArray/deValidados).
 *
 * Regra unica, a do docs/plans/plano_dtos.md: distinguir dado INVALIDO, AUSENTE
 * e ZERO/FALSO. Por isso:
 *
 *  - as variantes *OrNull devolvem null para ausente ou invalido -- texto nao
 *    numerico nunca vira 0, e "abc" nunca vira medicao zero;
 *  - booleanos entendem "false", "0", "off", "nao" como false (o (bool) do PHP
 *    transforma a string "false" em true);
 *  - UUID e string: nada de (int) em chave que pode comecar com "0199...".
 *
 * As variantes sem OrNull recebem um default explicito e o devolvem nos mesmos
 * casos. Use-as so em CREATE, onde ha um valor inicial legitimo; em UPDATE
 * parcial a ausencia deve preservar a coluna, entao prefira *OrNull junto com
 * array_key_exists (chave presente x ausente) no proprio DTO.
 */
trait SafeCastingTrait
{
    private const VERDADEIROS = ['1', 'true', 'on', 'yes', 'sim', 's', 'y'];
    private const FALSOS = ['0', 'false', 'off', 'no', 'nao', 'não', 'n'];

    protected static function asString(mixed $valor, string $default = ''): string
    {
        return self::asStringOrNull($valor) ?? $default;
    }

    /** Ausente, vazio ou nao escalar viram null. */
    protected static function asStringOrNull(mixed $valor): ?string
    {
        if ($valor instanceof Stringable) {
            $valor = (string) $valor;
        }

        if (is_bool($valor) || ! is_scalar($valor)) {
            return null;
        }

        $texto = (string) $valor;

        return $texto === '' ? null : $texto;
    }

    protected static function asInt(mixed $valor, int $default = 0): int
    {
        return self::asIntOrNull($valor) ?? $default;
    }

    /**
     * Inteiro, string so de digitos (com sinal opcional) ou float sem parte
     * fracionaria. "12abc", "1.5" e true sao invalidos: null, nao truncamento.
     */
    protected static function asIntOrNull(mixed $valor): ?int
    {
        if (is_int($valor)) {
            return $valor;
        }

        if (is_float($valor)) {
            return is_finite($valor) && floor($valor) === $valor ? (int) $valor : null;
        }

        if (is_string($valor) && preg_match('/^\s*[+-]?\d+\s*$/', $valor) === 1) {
            $filtrado = filter_var(trim($valor), FILTER_VALIDATE_INT);

            return $filtrado === false ? null : $filtrado;
        }

        return null;
    }

    protected static function asFloat(mixed $valor, float $default = 0.0): float
    {
        return self::asFloatOrNull($valor) ?? $default;
    }

    /**
     * Aceita tambem o formato brasileiro: "1,5" e "1.234,56". Com ponto e
     * virgula juntos, o ponto e separador de milhar. Texto nao numerico, NaN e
     * infinito viram null.
     */
    protected static function asFloatOrNull(mixed $valor): ?float
    {
        if (is_int($valor)) {
            return (float) $valor;
        }

        if (is_float($valor)) {
            return is_finite($valor) ? $valor : null;
        }

        if (! is_string($valor)) {
            return null;
        }

        $texto = trim($valor);

        if (str_contains($texto, ',')) {
            $texto = str_replace(',', '.', str_replace('.', '', $texto));
        }

        if ($texto === '' || ! is_numeric($texto)) {
            return null;
        }

        $numero = (float) $texto;

        return is_finite($numero) ? $numero : null;
    }

    protected static function asBool(mixed $valor, bool $default = false): bool
    {
        return self::asBoolOrNull($valor) ?? $default;
    }

    /** Valor fora das listas (ou ausente) vira null: quem chama decide o que fazer. */
    protected static function asBoolOrNull(mixed $valor): ?bool
    {
        if (is_bool($valor)) {
            return $valor;
        }

        if (is_int($valor)) {
            return match ($valor) {
                1 => true,
                0 => false,
                default => null,
            };
        }

        if (! is_string($valor)) {
            return null;
        }

        $texto = mb_strtolower(trim($valor));

        return match (true) {
            in_array($texto, self::VERDADEIROS, true) => true,
            in_array($texto, self::FALSOS, true) => false,
            default => null,
        };
    }

    /** UUID valido (qualquer versao) ou null. Nunca converte para inteiro. */
    protected static function asUuidOrNull(mixed $valor): ?string
    {
        $texto = self::asStringOrNull($valor);

        return $texto !== null && Str::isUuid(trim($texto)) ? strtolower(trim($texto)) : null;
    }

    /** @return array<array-key, mixed> */
    protected static function asArray(mixed $valor): array
    {
        return is_array($valor) ? $valor : [];
    }
}
