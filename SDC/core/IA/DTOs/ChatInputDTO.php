<?php

declare(strict_types=1);

namespace App\Core\IA\DTOs;

final readonly class ChatInputDTO
{
    public function __construct(
        public string $message,
        public ?string $conversationId = null,
        public ?string $intent = null,
        public array $options = []
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            message: $data['message'],
            conversationId: $data['conversation_id'] ?? null,
            intent: $data['intent'] ?? null,
            options: $data['options'] ?? []
        );
    }
}
