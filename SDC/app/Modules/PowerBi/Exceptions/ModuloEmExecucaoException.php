<?php

declare(strict_types=1);

namespace App\Modules\PowerBi\Exceptions;

use RuntimeException;

/** Ja existe uma execucao em curso para o modulo (lock ocupado). */
final class ModuloEmExecucaoException extends RuntimeException
{
    public static function modulo(string $modulo): self
    {
        return new self("Modulo {$modulo} ja esta em execucao.");
    }
}
