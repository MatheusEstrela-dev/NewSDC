<?php

declare(strict_types=1);

namespace App\Modules\Suporte\DTOs;

/**
 * Abertura de chamado de suporte.
 *
 * O anexo nao tem coluna em support_tickets: o caminho segue no DTO e o
 * Service o associa a primeira mensagem do chamado (support_ticket_messages
 * .attachment_path). Antes o arquivo era validado e descartado.
 */
final readonly class CreateTicketDTO
{
    public function __construct(
        public int $userId,
        public string $subject,
        public string $category,
        public string $priority,
        public string $description,
        public ?string $attachmentPath = null
    ) {}

    /**
     * @param  array{subject: string, category: string, priority: string, description: string}  $dados  validated()
     */
    public static function fromValidated(array $dados, int $userId, ?string $attachmentPath = null): self
    {
        return new self(
            userId: $userId,
            subject: $dados['subject'],
            category: $dados['category'],
            priority: $dados['priority'],
            description: $dados['description'],
            attachmentPath: $attachmentPath,
        );
    }

    /** Colunas de support_tickets (o anexo vai para a mensagem inicial). */
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
