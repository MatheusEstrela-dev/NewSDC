<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Domain\Etl;

/**
 * Parser das coordenadas do legado, que eram varchar(150) de texto livre.
 *
 * O formulario nao validava nada, e o SELECT DISTINCT sobre as 8.105 linhas
 * devolveu 21 formatos diferentes na mesma coluna:
 *
 *   -16.393269        6.814   ja utilizavel
 *   -05.033800_       1.039   sobrou o underscore da mascara do formulario
 *   -15035664           127   SEM separador decimal -- estoura numeric(10,7)
 *   15.224144S           17   hemisferio como sufixo, valor positivo
 *   16º41'27"             7   grau-minuto-segundo
 *   -15.601.703           1   ponto de milhar
 *   .                    53   so o separador
 *   -lo.caliza            1   texto
 *
 * Sem tratamento isso custava caro nas duas pontas: os 127 sem separador
 * derrubavam o INSERT inteiro (SQLSTATE 22003), fazendo o cadastro do
 * beneficiario ser perdido por causa de um campo, e os 1.039 com sufixo eram
 * descartados em silencio por `is_numeric()`.
 *
 * O valor original nunca se perde: continua em cisterna_legado_raw.doc.
 */
final class Coordenada
{
    /**
     * Minas Gerais com um grau de folga em cada lado. Serve para dois fins:
     * decidir onde entra o separador decimal ausente e rejeitar o que nao e
     * coordenada.
     *
     * O limite e estadual, e nao nacional, porque o programa de cisternas e da
     * CEDEC-MG e os 55 municipios atendidos estao todos dentro do estado. Com
     * limite nacional, `.162823` -- que e uma coordenada truncada -- passaria
     * como um ponto valido no Amapa, e `-01.4987252` como um no Amazonas.
     *
     * O intervalo ser todo negativo tambem e o que permite deduzir o
     * hemisferio das 22 linhas que gravaram o valor positivo sem o 'S'.
     */
    private const LAT_MIN = -24.0;

    private const LAT_MAX = -13.0;

    private const LON_MIN = -52.0;

    private const LON_MAX = -39.0;

    public static function latitude(mixed $valor): ?float
    {
        return self::interpretar($valor, self::LAT_MIN, self::LAT_MAX);
    }

    public static function longitude(mixed $valor): ?float
    {
        return self::interpretar($valor, self::LON_MIN, self::LON_MAX);
    }

    private static function interpretar(mixed $valor, float $min, float $max): ?float
    {
        $texto = trim((string) ($valor ?? ''));

        if ($texto === '') {
            return null;
        }

        $negativo = self::ehNegativo($texto);

        // Sobram digitos e separadores decimais. Underscore, grau, aspas,
        // letra de hemisferio e sinal saem daqui.
        $limpo = preg_replace('/[^0-9.,]/', ' ', $texto) ?? '';
        $grupos = preg_split('/[^0-9.,]+/', trim($limpo), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if ($grupos === []) {
            return null;
        }

        $graus = count($grupos) > 1
            ? self::deGrauMinutoSegundo($grupos)
            : self::deDecimal($grupos[0], $min, $max);

        if ($graus === null) {
            return null;
        }

        $graus = $negativo ? -abs($graus) : $graus;

        // Hemisferio ausente e comum: 22 linhas gravaram o valor positivo sem
        // 'S'. Se o intervalo aceito for todo negativo, o sinal e dedutivel.
        if ($graus > 0 && $max <= 0.0) {
            $graus = -$graus;
        }

        return $graus >= $min && $graus <= $max ? round($graus, 7) : null;
    }

    private static function ehNegativo(string $texto): bool
    {
        // 'S' de sul e 'W'/'O' de oeste valem sinal negativo, como o '-'.
        return str_starts_with($texto, '-')
            || (bool) preg_match('/[SsWwOo]\s*$/', $texto);
    }

    /**
     * Um grupo unico de digitos, com ou sem separador decimal.
     */
    private static function deDecimal(string $grupo, float $min, float $max): ?float
    {
        $normalizado = str_replace(',', '.', $grupo);
        $partes = explode('.', $normalizado);

        if (count($partes) > 2) {
            // `-15.601.703`: ponto de milhar. O primeiro ponto e o decimal.
            $normalizado = array_shift($partes).'.'.implode('', $partes);
        }

        if (! is_numeric($normalizado)) {
            return null;
        }

        $numero = (float) $normalizado;

        if (abs($numero) <= max(abs($min), abs($max))) {
            return $numero;
        }

        // Sem separador: `-15035664` e -15.035664. Minas cabe inteira em duas
        // casas de grau nos dois eixos (14..23 e 40..51), entao o separador
        // entra sempre depois da segunda.
        $digitos = preg_replace('/\D/', '', $normalizado) ?? '';

        if (strlen($digitos) <= 2) {
            return null;
        }

        $recuperado = (float) (substr($digitos, 0, 2).'.'.substr($digitos, 2));

        return $recuperado <= max(abs($min), abs($max)) ? $recuperado : null;
    }

    /**
     * `16º41'27"` e `16°45'43.24"S` -> grau decimal.
     *
     * @param  array<int, string>  $grupos
     */
    private static function deGrauMinutoSegundo(array $grupos): ?float
    {
        $numeros = [];

        foreach (array_slice($grupos, 0, 3) as $grupo) {
            $normalizado = str_replace(',', '.', $grupo);

            if (! is_numeric($normalizado)) {
                return null;
            }

            $numeros[] = (float) $normalizado;
        }

        if ($numeros === []) {
            return null;
        }

        return $numeros[0]
            + (($numeros[1] ?? 0.0) / 60)
            + (($numeros[2] ?? 0.0) / 3600);
    }
}
