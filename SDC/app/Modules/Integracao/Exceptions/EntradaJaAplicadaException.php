<?php

declare(strict_types=1);

namespace App\Modules\Integracao\Exceptions;

use RuntimeException;

/**
 * O efeito da mensagem ja existe no sdc (repeticao). O job marca a mensagem
 * como aplicada com o resultado informado, sem tratar como falha.
 */
final class EntradaJaAplicadaException extends RuntimeException
{
    /** @param array<string, mixed> $resultado */
    public function __construct(string $motivo, public readonly array $resultado = [])
    {
        parent::__construct($motivo);
    }
}
