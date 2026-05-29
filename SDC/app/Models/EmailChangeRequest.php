<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailChangeRequest extends Model
{
    public const MAX_ATTEMPTS = 5;
    public const TTL_MINUTES = 15;
    public const RESEND_COOLDOWN_SECONDS = 60;
    public const MAX_RESENDS_PER_REQUEST = 5;

    protected $fillable = [
        'user_id',
        'current_email',
        'new_email',
        'code_hash',
        'expires_at',
        'requested_ip',
        'requested_user_agent',
        'requested_by_admin_id',
    ];

    protected $casts = [
        'expires_at'      => 'datetime',
        'used_at'         => 'datetime',
        'cancelled_at'    => 'datetime',
        'last_resend_at'  => 'datetime',
        'code_attempts'   => 'integer',
        'resend_count'    => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function requestedByAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_admin_id');
    }

    /**
     * Verdadeiro enquanto o pedido pode ser confirmado.
     */
    public function isPending(): bool
    {
        return $this->used_at === null
            && $this->cancelled_at === null
            && $this->expires_at !== null
            && $this->expires_at->isFuture()
            && $this->code_attempts < self::MAX_ATTEMPTS;
    }

    /**
     * Escopo: pedidos ativos (nao usados, nao cancelados) de um usuario.
     */
    public function scopeActiveFor(Builder $q, int $userId): void
    {
        $q->where('user_id', $userId)
          ->whereNull('used_at')
          ->whereNull('cancelled_at');
    }
}
