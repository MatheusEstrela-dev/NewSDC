<?php

declare(strict_types=1);

namespace App\Modules\Pae\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Dilacao do prazo de uma notificacao (Art. 11): dias concedidos pela CEDEC
 * alem dos 30. Pertence a notificacao; protocolo_id fica para consulta direta.
 */
class PaeDilacao extends Model
{
    public const STATUS_APROVADA = 'APROVADA';

    protected $table = 'pae_dilacoes';

    protected $fillable = [
        'protocolo_id',
        'pae_notificacao_id',
        'status',
        'dias_adicionais',
        'justificativa',
        'aprovado_por',
    ];

    protected $casts = [
        'dias_adicionais' => 'integer',
    ];

    public function notificacao(): BelongsTo
    {
        return $this->belongsTo(PaeNotificacao::class, 'pae_notificacao_id');
    }

    public function protocolo(): BelongsTo
    {
        return $this->belongsTo(PaeProtocolo::class, 'protocolo_id');
    }

    public function aprovador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aprovado_por');
    }
}
