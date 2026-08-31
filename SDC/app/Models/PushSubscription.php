<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Um navegador/dispositivo autorizado a receber Web Push.
 *
 * Nao confundir com a preferencia: canal_push em user_notification_preferences e
 * a vontade do usuario; esta tabela e o endereco de entrega. Querer push sem
 * nenhuma inscricao ativa simplesmente nao entrega nada.
 */
class PushSubscription extends Model
{
    protected $fillable = [
        'user_id',
        'endpoint',
        'endpoint_hash',
        'public_key',
        'auth_token',
        'content_encoding',
        'user_agent',
        'last_used_at',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
    ];

    protected $hidden = [
        // Material criptografico da inscricao: nunca precisa voltar para a tela.
        'public_key',
        'auth_token',
        'endpoint',
        'endpoint_hash',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Identidade da inscricao. O endpoint e longo demais para um unique direto,
     * entao o hash e quem carrega a restricao.
     */
    public static function hashDoEndpoint(string $endpoint): string
    {
        return hash('sha256', $endpoint);
    }

    /**
     * Formato que a lib minishlink/web-push espera em Subscription::create().
     *
     * @return array<string, mixed>
     */
    public function paraEnvio(): array
    {
        return [
            'endpoint' => $this->endpoint,
            'keys' => [
                'p256dh' => $this->public_key,
                'auth' => $this->auth_token,
            ],
            'contentEncoding' => $this->content_encoding ?: 'aesgcm',
        ];
    }

    /**
     * Rotulo curto para a tela de Configuracoes ("Chrome no Windows").
     *
     * Heuristica simples de proposito: serve so para o usuario reconhecer qual
     * maquina e a dele antes de remover a inscricao.
     */
    public function apelido(): string
    {
        $ua = (string) $this->user_agent;

        $navegador = match (true) {
            str_contains($ua, 'Edg/') => 'Edge',
            str_contains($ua, 'OPR/') => 'Opera',
            str_contains($ua, 'Firefox') => 'Firefox',
            str_contains($ua, 'Chrome') => 'Chrome',
            str_contains($ua, 'Safari') => 'Safari',
            default => 'Navegador',
        };

        $sistema = match (true) {
            str_contains($ua, 'Windows') => 'Windows',
            str_contains($ua, 'Android') => 'Android',
            str_contains($ua, 'iPhone'), str_contains($ua, 'iPad') => 'iOS',
            str_contains($ua, 'Mac OS') => 'macOS',
            str_contains($ua, 'Linux') => 'Linux',
            default => 'dispositivo desconhecido',
        };

        return "{$navegador} no {$sistema}";
    }
}
