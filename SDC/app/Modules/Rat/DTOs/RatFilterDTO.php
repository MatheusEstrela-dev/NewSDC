<?php

declare(strict_types=1);

namespace App\Modules\Rat\DTOs;

class RatFilterDTO
{
    public function __construct(
        public readonly ?string $protocolo  = null,
        public readonly ?string $status     = null,
        public readonly ?string $municipio  = null,
        public readonly ?string $ano        = null,
        public readonly ?string $dataInicio = null,
        public readonly ?string $dataFim    = null,
        public readonly int     $perPage    = 15,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            protocolo:  $data['protocolo']   ?? null,
            status:     $data['status']      ?? null,
            municipio:  $data['municipio']   ?? null,
            ano:        $data['ano']         ?? null,
            dataInicio: $data['data_inicio'] ?? null,
            dataFim:    $data['data_fim']    ?? null,
            perPage:    (int) ($data['per_page'] ?? 15),
        );
    }
}
