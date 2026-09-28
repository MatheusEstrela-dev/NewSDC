<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Support;

/**
 * Guarda unica contra formula injection nos exports CSV do modulo.
 *
 * Um valor livre de cadastro (assunto, titulo, protocolo) que comece com
 * =, +, -, @ (ignorando espacos a esquerda) e interpretado como formula por
 * Excel/LibreOffice ao abrir o CSV. Neutraliza prefixando com aspas simples,
 * o que forca leitura como texto sem alterar o conteudo visivel.
 */
final class CsvSeguro
{
    public static function celula(mixed $valor): string
    {
        $texto = (string) $valor;

        return preg_match('/^[\s]*[=+\-@]/u', $texto) ? "'".$texto : $texto;
    }
}
