<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Integracao pessoal por usuario (Telegram, WhatsApp, N8N webhook).
 *
 * Cada user pode ter multiplas integracoes (uma por provider). Tokens e
 * secrets sao armazenados encriptados via cast 'encrypted' do Laravel —
 * primeiro modelo do projeto a adotar esse padrao at-rest.
 */
class UserIntegration extends Model
{
    public const PROVIDER_TELEGRAM = 'telegram';
    public const PROVIDER_WHATSAPP = 'whatsapp';
    public const PROVIDER_N8N = 'n8n_webhook';

    public const PROVIDERS = [
        self::PROVIDER_TELEGRAM,
        self::PROVIDER_WHATSAPP,
        self::PROVIDER_N8N,
    ];

    protected $fillable = [
        'user_id',
        'provider',
        'external_id',
        'display_name',
        'secret',
        'meta',
        'verified_at',
        'last_used_at',
        'is_active',
    ];

    protected $casts = [
        'secret' => 'encrypted',
        'meta' => 'array',
        'verified_at' => 'datetime',
        'last_used_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    protected $hidden = [
        'secret',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeVerified(Builder $query): Builder
    {
        return $query->whereNotNull('verified_at');
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeProvider(Builder $query, string $provider): Builder
    {
        return $query->where('provider', $provider);
    }

    public function markAsVerified(): void
    {
        $this->update(['verified_at' => now()]);
    }

    public function markAsUsed(): void
    {
        $this->update(['last_used_at' => now()]);
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }
}
