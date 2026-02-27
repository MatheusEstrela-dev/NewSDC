<?php

namespace App\Modules\Suporte\Services;

use App\Modules\Shared\BaseService;
use App\Modules\Suporte\DTOs\CreateTicketDTO;
use App\Modules\Suporte\Models\SupportTicket;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SupportTicketService extends BaseService
{
    public function create(CreateTicketDTO $dto): SupportTicket
    {
        return SupportTicket::create($dto->toArray());
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
