<?php

namespace App\Modules\Suporte\Application\UseCases;

use App\Modules\Suporte\Application\DTOs\CreateTicketDTO;
use App\Modules\Suporte\Domain\Entities\SupportTicket;
use App\Modules\Suporte\Domain\Repositories\SupportTicketRepositoryInterface;

class CreateTicketUseCase
{
    public function __construct(
        private readonly SupportTicketRepositoryInterface $repository
    ) {}

    public function execute(CreateTicketDTO $dto): SupportTicket
    {
        return $this->repository->create($dto->toArray());
        // Note: Attachment handling logic for initial ticket message should be here if we want to add an initial message or attachment column to ticket. 
        // For now, based on schema, attachment is on message. 
        // Simplification: We might want to create an initial message if there's an attachment or just store description in ticket.
        // The schema has description in ticket.
    }
}
