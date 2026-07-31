<?php

declare(strict_types=1);

namespace App\Modules\Notificacoes\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\DatabaseNotification;

/**
 * Notificacao persistida no inbox do usuario.
 *
 * Estende o DatabaseNotification do Laravel (mesma tabela, mesmo contrato de
 * leitura: markAsRead, read(), unread()) e acrescenta o agrupamento por janela.
 *
 * Nao usa Prunable de proposito: a retencao aqui ARQUIVA em vez de apagar, via
 * notificacoes:arquivar, que move as linhas antigas para notifications_archive.
 * Deixar Prunable junto abriria a porta para um model:prune apagar o que deveria
 * ter sido preservado.
 *
 * @property string|null $group_key
 * @property int|null $group_bucket
 * @property int $group_count
 * @property \Illuminate\Support\Carbon|null $last_event_at
 */
class Notificacao extends DatabaseNotification
{
    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
        'last_event_at' => 'datetime',
        'group_count' => 'integer',
        'group_bucket' => 'integer',
    ];

    public function scopeDoDestinatario(Builder $query, Model $notifiable): Builder
    {
        return $query
            ->where('notifiable_type', $notifiable->getMorphClass())
            ->where('notifiable_id', $notifiable->getKey());
    }

    public function scopeNaoLidas(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    /**
     * Ordem de exibicao do inbox: o fato mais recente primeiro. last_event_at so
     * existe em linhas agrupadas, por isso o COALESCE com created_at.
     */
    public function scopeMaisRecentesPrimeiro(Builder $query): Builder
    {
        return $query->orderByRaw('COALESCE(last_event_at, created_at) DESC');
    }

    public function ehAgrupada(): bool
    {
        return $this->group_count > 1;
    }
}
