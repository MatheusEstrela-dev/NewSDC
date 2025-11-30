<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model para logs de webhooks
 * Armazena histórico de envios e recebimentos para auditoria
 */
class WebhookLog extends Model
{
    protected $fillable = [
        'url',
        'payload',
        'status_code',
        'response',
        'duration_ms',
        'success',
        'user_id',
        'attempt',
    ];

    protected $casts = [
        'payload' => 'array',
        'success' => 'boolean',
        'duration_ms' => 'float',
        'created_at' => 'datetime',
    ];

    /**
     * Relação com usuário
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope para webhooks com falha
     */
    public function scopeFailed($query)
    {
        return $query->where('success', false);
    }

    /**
     * Scope para webhooks bem-sucedidos
     */
    public function scopeSuccessful($query)
    {
        return $query->where('success', true);
    }

    /**
     * Scope para webhooks lentos (acima de X ms)
     */
    public function scopeSlow($query, int $thresholdMs = 1000)
    {
        return $query->where('duration_ms', '>', $thresholdMs);
    }
}
