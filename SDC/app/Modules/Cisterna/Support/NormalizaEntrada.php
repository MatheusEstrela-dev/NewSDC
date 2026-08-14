<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Support;

/**
 * Normalizacao das mascaras que o formulario do legado enviava como texto.
 *
 * No legado isso vivia em duas closures declaradas dentro do controller,
 * duplicadas em store() e update() (CisternaController.php:788 e :1318).
 * Aqui e usada por prepareForValidation() dos FormRequests e pelo refino do
 * ETL, que le os mesmos formatos do banco legado.
 */
final class NormalizaEntrada
{
    /**
     * Devolve os 11 digitos do CPF, ou null se nao houver 11.
     * O legado guardava com mascara em varchar(150) e fazia str_replace em
     * quatro pontos diferentes, inclusive para montar nome de diretorio.
     */
    public static function cpf(?string $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        $digitos = preg_replace('/\D/', '', $valor) ?? '';

        return strlen($digitos) === 11 ? $digitos : null;
    }

    /**
     * Aceita "R$ 1.234,56", "1.234,56" e numero puro.
     */
    public static function moeda(mixed $valor): ?float
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        if (is_int($valor) || is_float($valor)) {
            return (float) $valor;
        }

        $limpo = str_replace(['R$', ' ', '.'], '', (string) $valor);
        $limpo = str_replace(',', '.', $limpo);

        return is_numeric($limpo) ? (float) $limpo : null;
    }

    /**
     * Aceita virgula como separador decimal, como o formulario do legado
     * enviava as medidas de telhado e testada.
     */
    public static function decimal(mixed $valor): ?float
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        if (is_int($valor) || is_float($valor)) {
            return (float) $valor;
        }

        $limpo = str_replace(',', '.', trim((string) $valor));

        return is_numeric($limpo) ? (float) $limpo : null;
    }

    /**
     * O legado usava 'sim'/'nao' nos campos sociais e '1'/'0' nos respAt*.
     */
    public static function booleanoSimNao(mixed $valor): ?bool
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        if (is_bool($valor)) {
            return $valor;
        }

        return in_array(strtolower(trim((string) $valor)), ['sim', '1', 'true', 's'], true);
    }
}
