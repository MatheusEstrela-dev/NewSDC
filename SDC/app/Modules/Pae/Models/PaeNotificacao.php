<?php

declare(strict_types=1);

namespace App\Modules\Pae\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaeNotificacao extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'pae_notificacoes';

    protected $fillable = [
        'num_sei',
        'user_id',
        'pae_analise_id',
        'dt_notificacao',
        'prorrogacao',
        'dt_devolutiva',
        'obs',
    ];

    protected $casts = [
        'dt_notificacao' => 'date',
        'dt_devolutiva'  => 'date',
        'prorrogacao'    => 'boolean',
    ];

    protected static function newFactory()
    {
        return \Database\Factories\Pae\PaeNotificacaoFactory::new();
    }

    public function analise(): BelongsTo
    {
        return $this->belongsTo(PaeAnalise::class, 'pae_analise_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
