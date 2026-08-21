<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Digito verificador do CNPJ (modulo 11 com pesos 2..9 ciclicos).
 *
 * Mesmo contrato do CpfValido: recebe o valor cru (mascarado ou nao), limpa os
 * separadores e checa os dois digitos. Rejeita tambem as sequencias de digito
 * repetido (00000000000000, 11111111111111...) que passam na conta de modulo 11
 * mas nunca foram emitidas pela Receita.
 */
class CnpjValido implements ValidationRule
{
    /** Pesos do 1o digito; o do 2o e este vetor precedido de 6. */
    private const PESOS_PRIMEIRO_DIGITO = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $cnpj = preg_replace('/\D/', '', (string) $value) ?? '';

        if (strlen($cnpj) !== 14 || preg_match('/^(\d)\1{13}$/', $cnpj)) {
            $fail('O CNPJ informado nao e valido.');

            return;
        }

        $pesosSegundo = array_merge([6], self::PESOS_PRIMEIRO_DIGITO);

        foreach ([12 => self::PESOS_PRIMEIRO_DIGITO, 13 => $pesosSegundo] as $posicao => $pesos) {
            if ((int) $cnpj[$posicao] !== self::digito($cnpj, $pesos)) {
                $fail('O CNPJ informado nao e valido.');

                return;
            }
        }
    }

    /**
     * @param  array<int, int>  $pesos
     */
    private static function digito(string $cnpj, array $pesos): int
    {
        $soma = 0;
        foreach ($pesos as $i => $peso) {
            $soma += (int) $cnpj[$i] * $peso;
        }

        $resto = $soma % 11;

        return $resto < 2 ? 0 : 11 - $resto;
    }
}
