<?php

declare(strict_types=1);

namespace App\Modules\Rat\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Arquivo/imagem anexado a um RAT.
 * Armazenado no disco público em rat/{rat_id}/{id}.{ext}
 */
class RatImagem extends Model
{
    use HasUuids;

    protected $table = 'rat_imagens';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'rat_id',
        'nome',
        'path',
        'tipo',
        'tamanho',
        'uploaded_by',
    ];

    protected $appends = ['url'];

    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->path);
    }

    public function rat(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Rat::class);
    }

    public function uploader(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'uploaded_by');
    }
}
