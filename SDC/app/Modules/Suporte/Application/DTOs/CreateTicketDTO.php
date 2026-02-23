<?php

namespace App\Modules\Suporte\Application\DTOs;

readonly class CreateTicketDTO
{
    public function __construct(
        public int $userId,
        public string $subject,
        public string $category,
        public string $priority,
        public string $description,
        public ?string $attachmentPath = null
    ) {}

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'subject' => $this->subject,
            'category' => $this->category,
            'priority' => $this->priority,
            'description' => $this->description,
        ];
    }
}
