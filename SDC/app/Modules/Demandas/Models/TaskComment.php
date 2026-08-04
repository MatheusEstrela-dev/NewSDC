<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Models;

use App\Models\User;
use App\Modules\Notificacoes\Enums\AcaoTrilha;
use App\Modules\Notificacoes\Support\TrilhaNoProtocoloPai;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskComment extends Model
{
    use TrilhaNoProtocoloPai;

    protected $table = 'task_comments';

    protected $fillable = [
        'task_id',
        'user_id',
        'tipo',
        'conteudo',
        'interno',
        'enviado_email',
        'metadata',
    ];

    protected $casts = [
        'interno' => 'boolean',
        'enviado_email' => 'boolean',
        'metadata' => 'array',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ─── Trilha de acoes no protocolo pai ───────────────────────────────────

    public function protocoloDaTrilhaClasse(): string
    {
        return Task::class;
    }

    /**
     * Comentario interno e nota entre a equipe: o solicitante nao ve esse texto na
     * tela, e avisa-lo de que "a demanda recebeu um comentario" o levaria a abrir a
     * demanda para procurar algo que nao esta la. Sem pai, sem trilha.
     */
    public function protocoloDaTrilhaChave(): int|string|null
    {
        return $this->interno ? null : $this->task_id;
    }

    public function acaoNaTrilhaDoProtocolo(): AcaoTrilha
    {
        return AcaoTrilha::Relacionado;
    }

    public function rotuloNaTrilhaDoProtocolo(): ?string
    {
        return 'um comentario';
    }
}
