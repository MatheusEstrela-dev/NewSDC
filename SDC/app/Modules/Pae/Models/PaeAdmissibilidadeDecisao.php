<?php

declare(strict_types=1);

namespace App\Modules\Pae\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PaeAdmissibilidadeDecisao extends Model
{
    protected $table = 'pae_admissibilidade_decisoes';

    protected $fillable = [
        'protocolo_id', 'tipo', 'fundamentacao', 'fundamentos',
        'checklist_snapshot', 'submetido_em', 'transitorio_confirmado',
        'notificado_em', 'prazo_correcao_em', 'num_sei',
        'decidido_por', 'decidido_em',
    ];

    protected $casts = [
        'fundamentos' => 'array',
        'checklist_snapshot' => 'array',
        'submetido_em' => 'date',
        'transitorio_confirmado' => 'boolean',
        'notificado_em' => 'date',
        'prazo_correcao_em' => 'date',
        'decidido_em' => 'datetime',
    ];

    public function protocolo(): BelongsTo
    {
        return $this->belongsTo(PaeProtocolo::class, 'protocolo_id');
    }

    public function decisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decidido_por');
    }
}
