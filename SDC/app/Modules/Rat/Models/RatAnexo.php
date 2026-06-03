<?php

declare(strict_types=1);

namespace App\Modules\Rat\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RatAnexo extends Model
{
    protected $table = 'rat_anexos';

    protected $fillable = [
        'rat_id',
        'user_id',
        'nome_original',
        'nome_arquivo',
        'mime_type',
        'tamanho_bytes',
        'path',
    ];

    protected $casts = [
        'tamanho_bytes' => 'integer',
    ];

    public function rat(): BelongsTo
    {
        return $this->belongsTo(Rat::class, 'rat_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
