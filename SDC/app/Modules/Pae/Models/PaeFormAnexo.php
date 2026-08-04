<?php

declare(strict_types=1);

namespace App\Modules\Pae\Models;

use App\Models\User;
use App\Modules\Notificacoes\Enums\AcaoTrilha;
use App\Modules\Notificacoes\Support\TrilhaNoProtocoloPai;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaeFormAnexo extends Model
{
    use TrilhaNoProtocoloPai;

    use SoftDeletes;

    protected $table = 'pae_form_anexos';

    protected $fillable = [
        'pae_form_id',
        'nome_original',
        'nome_arquivo',
        'mime_type',
        'tamanho_bytes',
        'path',
        'disk',
        'descricao',
        'uploaded_by',
    ];

    protected $casts = [
        'tamanho_bytes' => 'integer',
    ];

    public function form(): BelongsTo
    {
        return $this->belongsTo(PaeForm::class, 'pae_form_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getTamanhoFormatadoAttribute(): string
    {
        $bytes = $this->tamanho_bytes;

        if ($bytes >= 1_048_576) {
            return number_format($bytes / 1_048_576, 2) . ' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }

        return $bytes . ' bytes';
    }

    // ─── Trilha de acoes no protocolo pai ───────────────────────────────────

    public function protocoloDaTrilhaClasse(): string
    {
        return PaeProtocolo::class;
    }

    /**
     * Dois niveis: o anexo pende da ficha, e a ficha do protocolo. Resolver o protocolo
     * custa UM select por chave primaria, e so acontece no upload de um anexo -- acao
     * humana e isolada, nunca em lote.
     */
    public function protocoloDaTrilhaChave(): int|string|null
    {
        if ($this->pae_form_id === null) {
            return null;
        }

        return PaeForm::query()
            ->whereKey($this->pae_form_id)
            ->value('pae_protocolo_id');
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
