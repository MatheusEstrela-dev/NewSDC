<?php

declare(strict_types=1);

namespace App\Modules\NotasTecnicas\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** "Li e estou ciente" de um usuario sobre UMA versao. */
class NotaTecnicaLeitura extends Model
{
    public $timestamps = false;

    protected $table = 'nota_tecnica_leituras';

    protected $fillable = [
        'user_id',
        'nota_tecnica_versao_id',
        'lida_em',
    ];

    protected $casts = [
        'lida_em' => 'datetime',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function versao(): BelongsTo
    {
        return $this->belongsTo(NotaTecnicaVersao::class, 'nota_tecnica_versao_id');
    }
}
