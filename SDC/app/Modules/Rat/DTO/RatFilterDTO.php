<?php

declare(strict_types=1);

namespace App\Modules\Rat\DTO;

/**
 * DTO de filtro para a listagem de RAT (rat_ocorrencias).
 */
readonly class RatFilterDTO
{
    public function __construct(
        public readonly ?string $numeroBos  = null,
        public readonly ?int    $status     = null,
        public readonly ?string $dataInicio = null,
        public readonly ?string $dataFim    = null,
        public readonly int     $perPage    = 15,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            numeroBos:  $data['numero_bos']  ?? null,
            status:     isset($data['status']) ? (int) $data['status'] : null,
            dataInicio: $data['data_inicio']  ?? null,
            dataFim:    $data['data_fim']     ?? null,
            perPage:    (int) ($data['per_page'] ?? 15),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'numero_bos'  => $this->numeroBos,
            'status'      => $this->status,
            'data_inicio' => $this->dataInicio,
            'data_fim'    => $this->dataFim,
        ], fn ($v) => $v !== null);
    }
}
