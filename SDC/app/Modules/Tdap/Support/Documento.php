<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Support;

/**
 * Fonte unica das mascaras de CNPJ, telefone e CEP do TDAP.
 *
 * MOTIVO DE EXISTIR: `formatarCnpj` estava duplicado em PrestadorResource e
 * PrestadorIndexResource, e telefone/CEP nao eram formatados em lugar nenhum --
 * a tela Show exibia o valor cru do banco.
 *
 * CONTRATO DE ARMAZENAMENTO: no banco estes campos guardam SOMENTE DIGITOS
 * (garantido pelo PrestadorDTO na escrita e pela migration de normalizacao no
 * acervo legado). A mascara e responsabilidade da camada de exibicao -- por isso
 * ela vive aqui, e nao no Model.
 *
 * TOLERANCIA A LEGADO: valor com quantidade de digitos fora do padrao volta
 * como veio, sem mascara. Preferimos exibir "38999559" a inventar separador em
 * cima de um numero truncado.
 */
final class Documento
{
    /** 00.000.000/0000-00 */
    public static function cnpj(?string $valor): ?string
    {
        $digitos = self::digitos($valor);

        if ($digitos === null) {
            return null;
        }

        if (strlen($digitos) !== 14) {
            return $digitos;
        }

        return substr($digitos, 0, 2).'.'
            .substr($digitos, 2, 3).'.'
            .substr($digitos, 5, 3).'/'
            .substr($digitos, 8, 4).'-'
            .substr($digitos, 12, 2);
    }

    /** (00) 00000-0000 (celular) ou (00) 0000-0000 (fixo). */
    public static function telefone(?string $valor): ?string
    {
        $digitos = self::digitos($valor);

        if ($digitos === null) {
            return null;
        }

        $ddd = substr($digitos, 0, 2);
        $numero = substr($digitos, 2);

        return match (strlen($digitos)) {
            11      => "({$ddd}) ".substr($numero, 0, 5).'-'.substr($numero, 5),
            10      => "({$ddd}) ".substr($numero, 0, 4).'-'.substr($numero, 4),
            default => $digitos,
        };
    }

    /** 00000-000 */
    public static function cep(?string $valor): ?string
    {
        $digitos = self::digitos($valor);

        if ($digitos === null) {
            return null;
        }

        if (strlen($digitos) !== 8) {
            return $digitos;
        }

        return substr($digitos, 0, 5).'-'.substr($digitos, 5);
    }

    /**
     * Placa sem separador e em maiuscula: `BWA-6I04` vira `BWA6I04`.
     *
     * A normalizacao estava escrita em AbstractCaminhaoRequest e o
     * `mb_strtoupper` sozinho, de novo, no CaminhaoDTO -- quem montasse o DTO
     * fora do formulario (seeder, import, comando) gravava a placa com o hifen,
     * que e exatamente o formato de 131 das 132 linhas legadas hoje no banco.
     *
     * Vale para os dois padroes, o antigo (AAA1111) e o Mercosul (AAA1A11): os
     * dois tem 7 caracteres alfanumericos, e o que varia entre as bases e so o
     * separador.
     */
    public static function placa(?string $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        $placa = mb_strtoupper(preg_replace('/[^A-Za-z0-9]+/', '', $valor) ?? '');

        return $placa === '' ? null : $placa;
    }

    /** Extrai apenas os digitos; string vazia vira null. */
    public static function digitos(?string $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        $digitos = preg_replace('/\D+/', '', $valor) ?? '';

        return $digitos === '' ? null : $digitos;
    }
}
