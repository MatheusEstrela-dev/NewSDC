<?php

namespace App\Exceptions\Auth\EmailChange;

use RuntimeException;

class InvalidCodeException extends RuntimeException
{
    public function __construct(public readonly int $remaining)
    {
        parent::__construct("Codigo invalido. Restam {$remaining} tentativa(s).");
    }
}
