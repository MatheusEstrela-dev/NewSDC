<?php

namespace App\Modules\Suporte\Application\UseCases;

use App\Modules\Suporte\Domain\Repositories\SupportTicketRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListUserTicketsUseCase
{
    public function __construct(
        private readonly SupportTicketRepositoryInterface $repository
    ) {}

    public function execute(int $userId): LengthAwarePaginator
    {
        return $this->repository->listByUser($userId);
    }
}
