<?php

namespace App\Exceptions\Auth\EmailChange;

use RuntimeException;

class SameEmailException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('O novo e-mail e igual ao atual.');
    }
}
