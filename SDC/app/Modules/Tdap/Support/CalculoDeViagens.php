<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Support;

/**
 * Quantas viagens um caminhao precisa para levar a agua prevista da alocacao.
 *
 *   viagens = agua_prevista / capacidade do caminhao, arredondado:
 *     - parte decimal abaixo de 0,5 (0,4 inclusive) -> para baixo  (13,43 -> 13)
 *     - parte decimal de 0,5 em diante              -> para cima   (13,50 -> 14)
 *     - nunca menos que 1: meia viagem nao se faz, o caminhao vai ou nao vai
 *
 * Unica fonte da regra. O modal de alocacao espelha a conta em
 * resources/js/Support/calculoViagensTdap.js so para mostrar o numero antes de
 * salvar; o valor gravado no modo automatico e o que sai daqui.
 */
final class CalculoDeViagens
{
    public const MINIMO_DE_VIAGENS = 1;

    /**
     * Casas usadas para limpar o ruido da divisao em ponto flutuante antes de
     * arredondar: 0,7 / 0,2 da 3,4999999999999996, e sem a limpeza viraria 3
     * em vez de 4. Seis casas estao muito abaixo da precisao dos m3 (duas).
     */
    private const CASAS_DE_PRECISAO = 6;

    public static function necessarias(float $aguaPrevistaM3, float $capacidadeM3): int
    {
        if ($capacidadeM3 <= 0) {
            throw new \DomainException('Caminhao sem capacidade cadastrada: nao ha como calcular as viagens.');
        }

        $razao = round($aguaPrevistaM3 / $capacidadeM3, self::CASAS_DE_PRECISAO);
        $viagens = (int) round($razao, 0, PHP_ROUND_HALF_UP);

        return max(self::MINIMO_DE_VIAGENS, $viagens);
    }
}
