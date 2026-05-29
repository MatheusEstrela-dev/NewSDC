<?php

namespace App\Exceptions\Auth\EmailChange;

use RuntimeException;

class CodeExpiredException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Codigo expirado. Solicite um novo.');
    }
}
