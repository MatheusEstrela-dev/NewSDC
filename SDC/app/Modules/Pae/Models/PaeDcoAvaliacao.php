<?php

declare(strict_types=1);

namespace App\Modules\Pae\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PaeDcoAvaliacao extends Model
{
    public $timestamps = false;

    protected $table = 'pae_dco_avaliacoes';

    protected $fillable = [
        'protocolo_id', 'resultado', 'fundamentacao', 'num_sei',
        'chave_idempotencia', 'decidido_por', 'decidido_em',
    ];

    protected $casts = [
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
