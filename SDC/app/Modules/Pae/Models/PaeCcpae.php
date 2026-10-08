<?php

declare(strict_types=1);

namespace App\Modules\Pae\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Certificado de Conformidade do PAE (Resolucao GMG 83/2024, Art. 2, V).
 * A tabela legada nao tem created_at: dt_emissao e o marco.
 */
class PaeCcpae extends Model
{
    public const CREATED_AT = null;

    public const STATUS_ATIVO = 'ATIVO';

    protected $table = 'pae_ccpae';

    protected $fillable = [
        'protocolo_id',
        'codigo',
        'dt_emissao',
        'dt_vencimento',
        'status',
        'dt_licenca_operacao',
        'emitido_por',
        'dco_avaliacao_id',
        'dco_documento_id',
    ];

    protected $casts = [
        'dt_emissao' => 'date',
        'dt_vencimento' => 'date',
        'dt_licenca_operacao' => 'date',
    ];

    public function protocolo(): BelongsTo
    {
        return $this->belongsTo(PaeProtocolo::class, 'protocolo_id');
    }

    public function emissor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'emitido_por');
    }

    public function avaliacaoDco(): BelongsTo
    {
        return $this->belongsTo(PaeDcoAvaliacao::class, 'dco_avaliacao_id');
    }

    public function documentoDco(): BelongsTo
    {
        return $this->belongsTo(PaeDcoDocumento::class, 'dco_documento_id');
    }
}
