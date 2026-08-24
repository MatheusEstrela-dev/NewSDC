<?php

namespace App\Models;

use App\Support\Auth\MagicCodeVerifiable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailChangeRequest extends Model
{
    // Constantes de politica (MAX_ATTEMPTS, TTL_MINUTES, RESEND_COOLDOWN_SECONDS,
    // MAX_RESENDS_PER_REQUEST) e isPending() vem do trait, compartilhados com a
    // verificacao de cadastro do cidadao (CidadaoEmailVerificacao).
    use MagicCodeVerifiable;

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
     * Escopo: pedidos ativos (nao usados, nao cancelados) de um usuario.
     */
    public function scopeActiveFor(Builder $q, int $userId): void
    {
        $q->where('user_id', $userId)
          ->whereNull('used_at')
          ->whereNull('cancelled_at');
    }
}
