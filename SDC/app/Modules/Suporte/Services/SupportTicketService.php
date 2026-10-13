<?php

namespace App\Modules\Suporte\Services;

use App\Modules\Shared\BaseService;
use App\Modules\Suporte\DTOs\CreateTicketDTO;
use App\Modules\Suporte\Models\SupportTicket;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class SupportTicketService extends BaseService
{
    /**
     * Cria o chamado e, havendo anexo, a mensagem inicial que o carrega
     * (support_tickets nao tem coluna de anexo). Mesma transacao: chamado sem
     * o anexo que o usuario enviou nao fica gravado pela metade.
     */
    public function create(CreateTicketDTO $dto): SupportTicket
    {
        return DB::transaction(function () use ($dto) {
            $ticket = SupportTicket::create($dto->toArray());

            if ($dto->attachmentPath !== null) {
                $ticket->messages()->create([
                    'user_id'         => $dto->userId,
                    'message'         => 'Anexo enviado na abertura do chamado.',
                    'attachment_path' => $dto->attachmentPath,
                ]);
            }

            return $ticket;
        });
    }

    public function find(int $id): ?SupportTicket
    {
        return SupportTicket::with(['messages.user', 'user'])->find($id);
    }

    public function listByUser(int $userId, int $perPage = 10): LengthAwarePaginator
    {
        return SupportTicket::where('user_id', $userId)
            // So as mensagens com anexo: alimentam TicketResource::anexos.
            ->with(['messages' => fn ($q) => $q->whereNotNull('attachment_path')])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function update(SupportTicket $ticket, array $data): SupportTicket
    {
        $ticket->update($data);
        return $ticket;
    }
}
