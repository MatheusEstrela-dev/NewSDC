<?php

declare(strict_types=1);

namespace App\Modules\Notificacoes\Concerns;

use App\Modules\Notificacoes\Models\Notificacao;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Notifications\Notifiable;

/**
 * Notifiable do projeto: o inbox e lido pelo model Notificacao, e nao pelo
 * DatabaseNotification do Laravel.
 *
 * O DatabaseNotification nao declara conexao e herdaria a do destinatario (a
 * default). Notificacao usa a conexao de log, entao $user->notifications(),
 * readNotifications() e unreadNotifications() passam a ler pela mesma sessao
 * em que o canal grava.
 */
trait Notificavel
{
    use Notifiable;

    /**
     * @return MorphMany<Notificacao, $this>
     */
    public function notifications(): MorphMany
    {
        return $this->morphMany(Notificacao::class, 'notifiable')->latest();
    }
}
