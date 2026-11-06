<?php

declare(strict_types=1);

namespace App\Modules\Integracao\Exceptions;

use RuntimeException;

/** Mensagem que nunca vai aplicar (ex.: dono do token removido): rejeitada sem retentativa. */
final class EntradaRejeitadaException extends RuntimeException
{
}
