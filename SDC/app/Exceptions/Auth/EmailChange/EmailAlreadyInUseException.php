<?php

namespace App\Exceptions\Auth\EmailChange;

use RuntimeException;

class EmailAlreadyInUseException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Este e-mail ja esta em uso por outro usuario.');
    }
}
