<?php

namespace App\Modules\Suporte\Domain\Repositories;

use App\Modules\Suporte\Domain\Entities\SupportTicket;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface SupportTicketRepositoryInterface
{
    public function create(array $data): SupportTicket;
    public function find(int $id): ?SupportTicket;
    public function listByUser(int $userId, int $perPage = 10): LengthAwarePaginator;
    public function update(SupportTicket $ticket, array $data): SupportTicket;
}
