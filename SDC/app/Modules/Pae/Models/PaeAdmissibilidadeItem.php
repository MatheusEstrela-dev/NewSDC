<?php

declare(strict_types=1);

namespace App\Modules\Pae\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PaeAdmissibilidadeItem extends Model
{
    protected $table = 'pae_admissibilidade_itens';

    protected $fillable = [
        'protocolo_id', 'chave', 'resultado', 'justificativa', 'updated_by',
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
