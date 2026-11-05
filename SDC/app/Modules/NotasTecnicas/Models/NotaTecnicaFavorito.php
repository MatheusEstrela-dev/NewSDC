<?php

declare(strict_types=1);

namespace App\Modules\NotasTecnicas\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Nota marcada como favorita por um usuario. */
class NotaTecnicaFavorito extends Model
{
    // So existe created_at (preenchido pelo banco); nao ha updated_at.
    public $timestamps = false;

    protected $table = 'nota_tecnica_favoritos';

    protected $fillable = [
        'user_id',
        'nota_tecnica_id',
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
