<?php

namespace App\Exceptions\Auth\EmailChange;

use RuntimeException;

class MaxResendsReachedException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Limite de reenvios atingido. Cancele e tente novamente.');
    }
}
