<?php

declare(strict_types=1);

namespace App\Modules\Rat\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RatOcorrenciaHistorico extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'rat_ocorrencia_historico';

    protected $fillable = [
        'ocorrencia_id',
        'user_id',
        'evento',
        'payload',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'payload'    => 'array',
        'created_at' => 'datetime',
    ];

    public function ocorrencia(): BelongsTo
    {
        return $this->belongsTo(RatOcorrencia::class, 'ocorrencia_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
