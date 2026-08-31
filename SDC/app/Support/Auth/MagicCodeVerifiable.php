<?php

declare(strict_types=1);

namespace App\Support\Auth;

/**
 * Ciclo de vida comum aos pedidos de verificacao por magic code do sistema:
 * App\Models\EmailChangeRequest (troca de e-mail do servidor) e
 * App\Modules\Treinamento\Models\CidadaoEmailVerificacao (cadastro do cidadao
 * no Portal de Treinamentos).
 *
 * Os dois compartilham o mesmo contrato de colunas - code_hash, expires_at,
 * code_attempts, resend_count, last_resend_at, used_at, cancelled_at - e por
 * isso as constantes de politica e o isPending() moram aqui, num lugar so.
 * Mudar o TTL ou o teto de tentativas passa a valer para os dois fluxos.
 */
trait MagicCodeVerifiable
{
    public const MAX_ATTEMPTS = 5;
    public const TTL_MINUTES = 15;
    public const RESEND_COOLDOWN_SECONDS = 60;
    public const MAX_RESENDS_PER_REQUEST = 5;

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
     * Segundos que ainda faltam para liberar um novo reenvio. Zero quando ja
     * pode reenviar.
     */
    public function resendCooldownRemaining(): int
    {
        if ($this->last_resend_at === null) {
            return 0;
        }

        $liberaEm = $this->last_resend_at->addSeconds(self::RESEND_COOLDOWN_SECONDS);

        return max(0, (int) ceil(now()->diffInSeconds($liberaEm, false)));
    }
}
