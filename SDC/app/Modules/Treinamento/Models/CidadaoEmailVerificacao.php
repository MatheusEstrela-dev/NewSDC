<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Models;

use App\Support\Auth\MagicCodeVerifiable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pedido de verificacao do e-mail informado no cadastro publico do Portal de
 * Treinamentos. Enquanto nao for confirmado, o Cidadao existe mas nao autentica
 * (ver CidadaoAuthService).
 *
 * Politica (TTL, teto de tentativas, cooldown e teto de reenvios) vem do trait
 * compartilhado com App\Models\EmailChangeRequest.
 */
class CidadaoEmailVerificacao extends Model
{
    use MagicCodeVerifiable;

    protected $table = 'cidadao_email_verificacoes';

    protected $fillable = [
        'cidadao_id',
        'email',
        'code_hash',
        'expires_at',
        'requested_ip',
        'requested_user_agent',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'last_resend_at' => 'datetime',
        'code_attempts' => 'integer',
        'resend_count' => 'integer',
    ];

    public function cidadao(): BelongsTo
    {
        return $this->belongsTo(Cidadao::class);
    }

    /**
     * Escopo: pedidos ativos (nao usados, nao cancelados) de um cidadao.
     */
    public function scopeActiveFor(Builder $q, int $cidadaoId): void
    {
        $q->where('cidadao_id', $cidadaoId)
            ->whereNull('used_at')
            ->whereNull('cancelled_at');
    }
}
