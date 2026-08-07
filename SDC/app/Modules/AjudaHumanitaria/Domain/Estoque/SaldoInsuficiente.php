<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Domain\Estoque;

use RuntimeException;

/**
 * A saida pedida deixaria o saldo negativo.
 *
 * Traduz a violacao do CHECK ajuda_h_saldos_nao_negativo_ck em linguagem de
 * dominio, para que a camada de aplicacao nao precise conhecer SQLSTATE.
 */
final class SaldoInsuficiente extends RuntimeException
{
    public static function para(int $materialAhId, int $depositoId, string $quantidade): self
    {
        return new self(
            "Saldo insuficiente para movimentar {$quantidade} do material {$materialAhId} no deposito {$depositoId}."
        );
    }
}
