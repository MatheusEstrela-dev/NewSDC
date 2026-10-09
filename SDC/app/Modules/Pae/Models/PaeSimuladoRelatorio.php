<?php

declare(strict_types=1);

namespace App\Modules\Pae\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Relatorio anual do Anexo C (imutavel; revisoes por dt_realizacao e versao, a maior versao vale). */
final class PaeSimuladoRelatorio extends Model
{
    public $timestamps = false;

    protected $table = 'pae_simulado_relatorios';

    protected $fillable = [
        'protocolo_id', 'dt_realizacao', 'versao', 'nivel_emergencia', 'dt_apresentacao', 'num_sei', 'observacao',
        'arquivo_path', 'arquivo_nome_original', 'arquivo_mime', 'arquivo_tamanho_bytes',
        'integrado', 'barragens_integradas', 'aviso_cedec_em',
        'criterios', 'tempos', 'alarme', 'informativos', 'validado', 'indicios',
        'chave_idempotencia', 'registrado_por', 'registrado_em',
    ];

    protected $casts = [
        'dt_realizacao' => 'date',
        'dt_apresentacao' => 'date',
        'aviso_cedec_em' => 'date',
        'versao' => 'integer',
        'nivel_emergencia' => 'integer',
        'arquivo_tamanho_bytes' => 'integer',
        'integrado' => 'boolean',
        'validado' => 'boolean',
        'criterios' => 'array',
        'tempos' => 'array',
        'alarme' => 'array',
        'informativos' => 'array',
        'indicios' => 'array',
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
