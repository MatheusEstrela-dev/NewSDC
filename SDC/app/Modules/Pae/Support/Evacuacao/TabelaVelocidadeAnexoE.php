<?php

declare(strict_types=1);

namespace App\Modules\Pae\Support\Evacuacao;

/**
 * Tabela 01 do Anexo E (adaptada de Rosaria Ono): velocidade de deslocamento
 * em m/s pela densidade e pelo terreno. Inclinado = declividade predominante
 * acima de 5%.
 */
final class TabelaVelocidadeAnexoE
{
    public const PLANO = 'plano';
    public const INCLINADO = 'inclinado';

    private const EPSILON = 1e-9;

    /** @var list<array{0: float, 1: float, 2: float}> limite superior, plano, inclinado */
    private const FAIXAS = [
        [0.54, 1.20, 1.05],
        [1.0, 1.03, 0.90],
        [1.5, 0.84, 0.74],
        [2.0, 0.66, 0.58],
    ];

    /** Null quando a formula de D > 2 nao produz velocidade positiva. */
    public static function velocidade(float $densidade, string $terreno): ?float
    {
        $plano = $terreno === self::PLANO;
        foreach (self::FAIXAS as [$limite, $velocidadePlano, $velocidadeInclinado]) {
            if ($densidade <= $limite + self::EPSILON) {
                return $plano ? $velocidadePlano : $velocidadeInclinado;
            }
        }
        $velocidade = $plano ? 1.4 - 0.372 * $densidade : 1.23 - 0.327 * $densidade;

        return $velocidade > self::EPSILON ? $velocidade : null;
    }
}
