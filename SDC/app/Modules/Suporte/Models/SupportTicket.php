<?php

declare(strict_types=1);

namespace App\Modules\Suporte\Models;

use App\Models\User;
use App\Modules\Notificacoes\Contracts\Rastreavel;
use App\Modules\Notificacoes\Support\TrilhaDeAcoes;
use App\Modules\Suporte\Enums\TicketCategory;
use App\Modules\Suporte\Enums\TicketPriority;
use App\Modules\Suporte\Enums\TicketStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicket extends Model implements Rastreavel
{
    use TrilhaDeAcoes;

    protected $fillable = [
        'user_id',
        'subject',
        'category',
        'status',
        'priority',
        'description',
    ];

    protected $casts = [
        'category' => TicketCategory::class,
        'status' => TicketStatus::class,
        'priority' => TicketPriority::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SupportTicketMessage::class);
    }

    // ─── Trilha de acoes (notificacao ao dono) ──────────────────────────────

    public function moduloNotificacao(): string
    {
        return 'suporte';
    }

    public function rotuloProtocolo(): string
    {
        $assunto = trim((string) $this->subject);

        return $assunto === ''
            ? 'Ticket #'.$this->getKey()
            : 'Ticket "'.$assunto.'"';
    }

    /**
     * @return list<int>
     */
    public function donosNotificacao(): array
    {
        return $this->user_id === null ? [] : [(int) $this->user_id];
    }

    /**
     * O modulo tem apenas index e store: nao existe rota de exibicao de um ticket.
     */
    public function urlNotificacao(): ?string
    {
        return '/suporte';
    }

    public function campoSituacao(): ?string
    {
        return 'status';
    }

    public function rotuloSituacao(): ?string
    {
        return $this->status instanceof TicketStatus ? $this->status->label() : null;
    }

    public function tipoSituacaoNotificacao(): ?string
    {
        return $this->status === TicketStatus::RESOLVED ? 'success' : 'info';
    }
}
