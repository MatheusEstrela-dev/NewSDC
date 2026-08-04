<?php

declare(strict_types=1);

namespace App\Modules\Suporte\Models;

use App\Models\User;
use App\Modules\Notificacoes\Enums\AcaoTrilha;
use App\Modules\Notificacoes\Support\TrilhaNoProtocoloPai;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportTicketMessage extends Model
{
    use TrilhaNoProtocoloPai;

    protected $fillable = [
        'support_ticket_id',
        'user_id',
        'message',
        'attachment_path',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'support_ticket_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ─── Trilha de acoes no protocolo pai ───────────────────────────────────

    public function protocoloDaTrilhaClasse(): string
    {
        return SupportTicket::class;
    }

    public function protocoloDaTrilhaChave(): int|string|null
    {
        return $this->support_ticket_id;
    }

    public function acaoNaTrilhaDoProtocolo(): AcaoTrilha
    {
        return AcaoTrilha::Relacionado;
    }

    public function rotuloNaTrilhaDoProtocolo(): ?string
    {
        return 'uma resposta';
    }
}
