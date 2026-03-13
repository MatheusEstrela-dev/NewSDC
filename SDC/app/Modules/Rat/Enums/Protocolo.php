<?php

declare(strict_types=1);

namespace App\Modules\Rat\Enums;

use InvalidArgumentException;

class Protocolo
{
    public function __construct(
        private readonly string $numero
    ) {
        $this->validate();
    }

    private function validate(): void
    {
        if (empty($this->numero)) {
            throw new InvalidArgumentException('Protocolo nao pode ser vazio');
        }
    }

    public function getNumero(): string
    {
        return $this->numero;
    }

    public function __toString(): string
    {
        return $this->numero;
    }
}
