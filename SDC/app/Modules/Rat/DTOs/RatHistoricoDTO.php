<?php

declare(strict_types=1);

namespace App\Modules\Rat\DTOs;

/**
 * DTO para dados do histórico e descrição da ocorrência RAT.
 */
readonly class RatHistoricoDTO
{
    public function __construct(
        public ?string $historico = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            historico: $data['historico'] ?? $data['descricao'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'historico' => $this->historico,
        ];
    }
}
