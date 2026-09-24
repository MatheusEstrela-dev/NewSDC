<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Domain\Exceptions;

use DomainException;

final class TransicaoProibidaException extends DomainException
{
    public static function entre(string $de, string $para): self
    {
        return new self(sprintf('Não é possível levar a demanda de "%s" para "%s".', $de, $para));
    }
}
