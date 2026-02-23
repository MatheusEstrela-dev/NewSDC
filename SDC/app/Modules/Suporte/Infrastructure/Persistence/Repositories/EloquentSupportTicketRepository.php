<?php

namespace App\Modules\Suporte\Infrastructure\Persistence\Repositories;

use App\Modules\Suporte\Domain\Entities\SupportTicket;
use App\Modules\Suporte\Domain\Repositories\SupportTicketRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentSupportTicketRepository implements SupportTicketRepositoryInterface
{
    public function create(array $data): SupportTicket
    {
        return SupportTicket::create($data);
    }

    public function find(int $id): ?SupportTicket
    {
        return SupportTicket::with(['messages.user', 'user'])->find($id);
    }

    public function listByUser(int $userId, int $perPage = 10): LengthAwarePaginator
    {
        return SupportTicket::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function update(SupportTicket $ticket, array $data): SupportTicket
    {
        $ticket->update($data);
        return $ticket;
    }
}
