<?php

declare(strict_types=1);

namespace App\Modules\Pae\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PaeChecklistResposta extends Model
{
    protected $table = 'pae_checklist_respostas';

    protected $fillable = [
        'protocolo_id', 'codigo', 'resposta', 'observacao', 'catalogo_versao', 'updated_by',
    ];

    protected $casts = [
        'catalogo_versao' => 'integer',
    ];

    public function protocolo(): BelongsTo
    {
        return $this->belongsTo(PaeProtocolo::class, 'protocolo_id');
    }

    public function atualizador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
