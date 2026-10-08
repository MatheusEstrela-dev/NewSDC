<?php

declare(strict_types=1);

namespace App\Modules\Pae\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PaeDcoDocumento extends Model
{
    public $timestamps = false;

    protected $table = 'pae_dco_documentos';

    protected $fillable = [
        'protocolo_id', 'competencia', 'versao', 'resultado', 'dt_documento',
        'dt_apresentacao', 'num_sei', 'observacao', 'arquivo_path',
        'arquivo_nome_original', 'arquivo_mime', 'arquivo_tamanho_bytes',
        'chave_idempotencia', 'registrado_por', 'registrado_em',
    ];

    protected $casts = [
        'competencia' => 'integer',
        'versao' => 'integer',
        'dt_documento' => 'date',
        'dt_apresentacao' => 'date',
        'arquivo_tamanho_bytes' => 'integer',
        'registrado_em' => 'datetime',
    ];

    public function protocolo(): BelongsTo
    {
        return $this->belongsTo(PaeProtocolo::class, 'protocolo_id');
    }

    public function registrador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }
}
