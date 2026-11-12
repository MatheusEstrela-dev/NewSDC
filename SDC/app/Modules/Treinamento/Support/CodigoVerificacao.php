<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Support;

/**
 * Codigo de verificacao do certificado (spec 8.5): aleatorio, nao sequencial
 * e legivel por humanos, no formato CEDEC-7K4P-29XQ. O alfabeto deixa de fora
 * os caracteres que se confundem ao ler ou digitar (I, L, O, 0, 1).
 */
final class CodigoVerificacao
{
    public const ALFABETO = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    private const PREFIXO = 'CEDEC';

    private const BLOCOS = 2;

    private const TAMANHO_BLOCO = 4;

    public static function gerar(): string
    {
        $blocos = [self::PREFIXO];
        $limite = strlen(self::ALFABETO) - 1;

        for ($b = 0; $b < self::BLOCOS; $b++) {
            $bloco = '';
            for ($i = 0; $i < self::TAMANHO_BLOCO; $i++) {
                $bloco .= self::ALFABETO[random_int(0, $limite)];
            }
            $blocos[] = $bloco;
        }

        return implode('-', $blocos);
    }

    /**
     * Forma canonica do que o usuario digitou (maiusculas, sem espacos, com ou
     * sem hifens), ou null se nao tem a forma de um codigo.
     */
    public static function normalizar(string $entrada): ?string
    {
        $limpo = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $entrada) ?? '');
        $corpo = self::BLOCOS * self::TAMANHO_BLOCO;

        if (! str_starts_with($limpo, self::PREFIXO) || strlen($limpo) !== strlen(self::PREFIXO) + $corpo) {
            return null;
        }

        $codigo = self::PREFIXO.'-'.implode('-', str_split(substr($limpo, strlen(self::PREFIXO)), self::TAMANHO_BLOCO));

        return preg_match('/^'.self::PREFIXO.'(-['.self::ALFABETO.']{'.self::TAMANHO_BLOCO.'}){'.self::BLOCOS.'}$/', $codigo) === 1
            ? $codigo
            : null;
    }
}
