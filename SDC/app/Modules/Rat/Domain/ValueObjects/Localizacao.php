<?php

namespace App\Modules\Rat\Domain\ValueObjects;

class Localizacao
{
    public function __construct(
        private readonly ?string $municipio,
        private readonly ?string $uf,
        private readonly int $paisId = 1
    ) {
    }

    public function getMunicipio(): ?string
    {
        return $this->municipio;
    }

    public function getUf(): ?string
    {
        return $this->uf;
    }

    public function getPaisId(): int
    {
        return $this->paisId;
    }

    public function getFormatted(): string
    {
        $parts = array_filter([$this->municipio, $this->uf]);
        return implode('/', $parts) ?: 'Não informado';
    }
}

