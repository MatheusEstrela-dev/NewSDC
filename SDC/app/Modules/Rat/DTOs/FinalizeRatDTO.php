<?php

declare(strict_types=1);

namespace App\Modules\Rat\DTOs;

readonly class FinalizeRatDTO
{
    public function __construct(
        public int|string $id,
        public int $userId,
        public ?string $observacao = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            userId: $data['user_id'],
            observacao: $data['observacao'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'observacao' => $this->observacao,
        ];
    }
}
