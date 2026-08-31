<?php

declare(strict_types=1);

namespace App\Modules\Pmda\Support;

/**
 * Faixa de coordenada aceita para comunidades do PMDA.
 *
 * A caixa envolvente de Minas Gerais, com folga nas divisas. E mais estrita que
 * a faixa terrestre de proposito: dentro de -90..90 passariam calados os tres
 * erros que de fato acontecem no formulario -- lixo numerico digitado no campo,
 * sinal negativo esquecido (o ponto vai para o hemisferio norte) e latitude
 * trocada com longitude. Nenhum deles da erro de sintaxe; todos caem fora daqui.
 *
 * As regras vivem aqui, e nao repetidas em cada FormRequest, porque solicitar
 * uma comunidade e cadastrar uma comunidade validam a mesma coisa.
 */
final class CoordenadaMG
{
    public const LAT_MIN = -23.2;
    public const LAT_MAX = -14.0;

    public const LON_MIN = -51.3;
    public const LON_MAX = -39.5;

    /** @return array<int, string> */
    public static function regrasLatitude(): array
    {
        return ['nullable', 'numeric', 'between:'.self::LAT_MIN.','.self::LAT_MAX];
    }

    /** @return array<int, string> */
    public static function regrasLongitude(): array
    {
        return ['nullable', 'numeric', 'between:'.self::LON_MIN.','.self::LON_MAX];
    }

    /** @return array<string, string> */
    public static function mensagens(): array
    {
        return [
            'latitude.between'  => 'A latitude deve estar dentro de Minas Gerais (entre '.self::LAT_MIN.' e '.self::LAT_MAX.'). Confira o sinal negativo e se não trocou com a longitude.',
            'latitude.numeric'  => 'A latitude deve ser um número, no formato -19.1234.',
            'longitude.between' => 'A longitude deve estar dentro de Minas Gerais (entre '.self::LON_MIN.' e '.self::LON_MAX.'). Confira o sinal negativo e se não trocou com a latitude.',
            'longitude.numeric' => 'A longitude deve ser um número, no formato -46.1231.',
        ];
    }
}
