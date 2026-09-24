<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Domain\Exceptions;

/**
 * Data da viagem depois do limite do cronograma.
 *
 * Subclasse de DomainException para quem ja trata o generico seguir
 * funcionando; o controller a distingue para devolver o erro no campo
 * `data_registro`, e nao num aviso solto que fecha o modal.
 */
final class ViagemForaDoLimiteException extends \DomainException
{
}
