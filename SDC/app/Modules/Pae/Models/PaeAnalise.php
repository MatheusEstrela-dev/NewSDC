<?php

declare(strict_types=1);

namespace App\Modules\Pae\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaeAnalise extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'pae_analises';

    protected $fillable = [
        'pae_protocolo_id',
        'user_id',
        'status',
        'parecer',
        'situacao',
        'obs',
        'tipo',
        'anexo',
    ];

    protected static function newFactory()
    {
        return \Database\Factories\Pae\PaeAnaliseFactory::new();
    }

    public function protocolo(): BelongsTo
    {
        return $this->belongsTo(PaeProtocolo::class, 'pae_protocolo_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function notificacoes(): HasMany
    {
        return $this->hasMany(PaeNotificacao::class, 'pae_analise_id')
            ->orderBy('dt_notificacao')
            ->orderBy('id');
    }
}
