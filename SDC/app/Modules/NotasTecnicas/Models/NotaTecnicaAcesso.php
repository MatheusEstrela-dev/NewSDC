<?php

declare(strict_types=1);

namespace App\Modules\NotasTecnicas\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Ultimo acesso de um usuario a uma nota (base da colecao Recentes). */
class NotaTecnicaAcesso extends Model
{
    public $timestamps = false;

    protected $table = 'nota_tecnica_acessos';

    protected $fillable = [
        'user_id',
        'nota_tecnica_id',
        'acessado_em',
    ];

    protected $casts = [
        'acessado_em' => 'datetime',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function nota(): BelongsTo
    {
        return $this->belongsTo(NotaTecnica::class, 'nota_tecnica_id');
    }
}
