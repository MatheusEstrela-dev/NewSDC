<?php

declare(strict_types=1);

namespace App\Modules\Rat\Application\DTOs;

/**
 * DTO para transferencia de dados na finalizacao de RAT.
 * Segue o principio SRP - apenas transporta dados.
 */
readonly class FinalizeRatDTO
{
    public function __construct(
        public int|string $id,
        public int $userId,
        public ?string $observacao = null
    ) {}

    /**
     * Cria DTO a partir de um array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            userId: $data['user_id'],
            observacao: $data['observacao'] ?? null
        );
    }

    /**
     * Converte para array.
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'observacao' => $this->observacao,
        ];
    }
}
