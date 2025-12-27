<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Entity: Log de Auditoria do Processo
 */
class ProcessoLog extends Model
{
    protected $table = 'processo_logs';

    protected $fillable = [
        'processo_id',
        'user_id',
        'acao',
        'dados_antes',
        'dados_depois',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'dados_antes' => 'array',
        'dados_depois' => 'array',
    ];

    public $timestamps = true;

    public function processo(): BelongsTo
    {
        return $this->belongsTo(Processo::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
