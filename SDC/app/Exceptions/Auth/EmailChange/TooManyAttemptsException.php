<?php

namespace App\Exceptions\Auth\EmailChange;

use RuntimeException;

class TooManyAttemptsException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Limite de tentativas atingido. Solicite um novo codigo.');
    }
}
