<?php

declare(strict_types=1);

namespace App\Modules\Rat\DTOs;

readonly class DeleteRatDTO
{
    public function __construct(
        public int|string $id,
        public int $userId,
        public ?string $motivo = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            userId: $data['user_id'],
            motivo: $data['motivo'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'motivo' => $this->motivo,
        ];
    }
}
