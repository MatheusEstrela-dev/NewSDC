<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Exceptions;

use RuntimeException;

/** Falha no processamento; `definitiva` indica que tentar de novo nao adianta. */
class FalhaDeVideo extends RuntimeException
{
    public function __construct(string $mensagem, public readonly bool $definitiva = false)
    {
        parent::__construct($mensagem);
    }
}
