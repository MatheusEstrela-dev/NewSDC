<?php

declare(strict_types=1);

namespace App\Modules\Integracao\Exceptions;

use RuntimeException;

/** Idempotency-Key ja usada com outro corpo ou outro recurso (HTTP 409). */
final class ChaveIdempotenteEmConflitoException extends RuntimeException
{
    public function __construct(public readonly string $mensagemOriginalId)
    {
        parent::__construct('Idempotency-Key ja utilizada com outro conteudo.');
    }
}
