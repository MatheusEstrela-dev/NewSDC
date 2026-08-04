<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Models;

use App\Models\User;
use App\Modules\Notificacoes\Enums\AcaoTrilha;
use App\Modules\Notificacoes\Support\TrilhaNoProtocoloPai;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskAttachment extends Model
{
    use TrilhaNoProtocoloPai;

    protected $table = 'task_attachments';

    protected $fillable = [
        'task_id',
        'user_id',
        'nome_original',
        'nome_arquivo',
        'mime_type',
        'tamanho_bytes',
        'path',
    ];

    protected $casts = [
        'tamanho_bytes' => 'integer',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Retorna tamanho formatado
     */
    public function getTamanhoFormatadoAttribute(): string
    {
        $bytes = $this->tamanho_bytes;

        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        }

        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }

        return $bytes . ' bytes';
    }

    // ─── Trilha de acoes no protocolo pai ───────────────────────────────────

    public function protocoloDaTrilhaClasse(): string
    {
        return Task::class;
    }

    public function protocoloDaTrilhaChave(): int|string|null
    {
        return $this->task_id;
    }

    public function acaoNaTrilhaDoProtocolo(): AcaoTrilha
    {
        return AcaoTrilha::Relacionado;
    }

    public function rotuloNaTrilhaDoProtocolo(): ?string
    {
        return 'um novo anexo';
    }
}
