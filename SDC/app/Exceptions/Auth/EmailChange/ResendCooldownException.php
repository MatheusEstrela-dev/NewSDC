<?php

namespace App\Exceptions\Auth\EmailChange;

use RuntimeException;

class ResendCooldownException extends RuntimeException
{
    public function __construct(public readonly int $secondsRemaining)
    {
        parent::__construct("Aguarde {$secondsRemaining}s antes de reenviar.");
    }
}
