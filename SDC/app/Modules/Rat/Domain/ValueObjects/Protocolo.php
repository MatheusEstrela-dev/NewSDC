<?php

namespace App\Modules\Rat\Domain\ValueObjects;

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
            throw new \InvalidArgumentException('Protocolo não pode ser vazio');
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

