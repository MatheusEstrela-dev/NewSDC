<?php

declare(strict_types=1);

namespace App\Modules\Pae\DTOs;

readonly class PaeFormObjetivoDTO
{
    public function __construct(
        public int $userId,
        public ?string $objetivo = null,
        public ?string $contextualizacao = null,
    ) {}

    public static function fromArray(array $data, int $userId): self
    {
        return new self(
            userId: $userId,
            objetivo: $data['objetivo'] ?? null,
            contextualizacao: $data['contextualizacao'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'objetivo'   => $this->objetivo,
            'contexto'   => $this->contextualizacao,
            'updated_by' => $this->userId,
        ];
    }
}
