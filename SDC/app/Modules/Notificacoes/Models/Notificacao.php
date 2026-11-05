<?php

declare(strict_types=1);

namespace App\Modules\Notificacoes\Models;

use App\Support\Database\UsaConexaoDeLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\DatabaseNotification;

/**
 * Notificacao persistida no inbox do usuario.
 *
 * Estende o DatabaseNotification do Laravel (mesma tabela, mesmo contrato de
 * leitura: markAsRead, read(), unread()) e acrescenta o agrupamento por janela.
 *
 * Vive na conexao de log (UsaConexaoDeLog), com sessao e pool proprios quando
 * DB_LOG_CONNECTION esta ligada.
 *
 * Retencao: o log:podar (config/retencao.php) apaga a lida com mais de 90 dias;
 * a nao lida nunca e apagada, por mais antiga que seja. O botao Limpar do sino
 * move para notifications_archive, que tambem sai depois de 90 dias. Nao usa
 * Prunable de proposito: um model:prune paralelo apagaria fora dessa regra.
 *
 * @property string|null $group_key
 * @property int|null $group_bucket
 * @property int $group_count
 * @property \Illuminate\Support\Carbon|null $last_event_at
 */
class Notificacao extends DatabaseNotification
{
    use UsaConexaoDeLog;

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
